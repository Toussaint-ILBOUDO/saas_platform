<?php

namespace Tests\Feature\Api\Bibliotheque;

use App\Models\Cabinet;
use App\Models\DocumentBibliotheque;
use App\Models\DocumentReaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Réactions (aime / n'aime pas), déduplication des accès et transfert des
 * réactions anonymes vers le compte au moment de la connexion.
 *
 * Verrouille :
 *  - toggle réaction + compteurs recalculés côté serveur ;
 *  - retrait d'une même réaction, remplacement par l'opposée ;
 *  - visiteur de session : une identité = une réaction ;
 *  - dédup vues / téléchargements sous 30 minutes (même session) ;
 *  - transfert anonyme → membre au login sans doublon.
 */
class DocumentReactionsTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function connecteMembre(string $slug, string $email, string $role): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        if (! Cabinet::find($slug)) {
            $this->makeCabinet($slug);
        }

        tenancy()->initialize($slug);
        $user = User::where('email', $email)->first() ?? User::create([
            'nom' => 'Membre',
            'prenom' => 'Reac',
            'email' => $email,
            'password' => Hash::make('Secret1234'),
        ]);
        $user->assignRole($role);
        $user->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function apiPublic(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/public/documents{$chemin}";
    }

    /** Dépôt enseignant + validation admin → document public publié. */
    private function publier(string $slug, string $titre): string
    {
        $this->connecteMembre($slug, "enseignant@{$slug}.local", 'enseignant');

        $typeId = $this->withTenant($slug, fn () => \App\Models\TypeDocument::query()->value('id'));

        $reponse = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->post("http://{$slug}.localhost/api/bibliotheque/documents", [
                'titre' => $titre,
                'type_document_id' => $typeId,
                'statut' => 'en_attente',
                'is_public' => '1',
                'resume' => 'Support de cours.',
                'fichier' => UploadedFile::fake()->create('support.pdf', 30, 'application/pdf'),
            ]);
        $reponse->assertStatus(201);
        $id = $reponse->json('data.id');

        $this->connecteMembre($slug, "admin@{$slug}.local", 'admin_cabinet');
        $this->patchJson("http://{$slug}.localhost/api/admin/bibliotheque/{$id}/valider")
            ->assertOk();

        $slugDoc = $this->withTenant($slug, fn () => DocumentBibliotheque::query()->value('slug'));

        // Logout réel + purge du guard : sans cela l'admin reste en cache
        // et la prochaine réaction « anonyme » lui serait attribuée.
        $this->postJson("http://{$slug}.localhost/api/auth/deconnexion")->assertOk();
        $this->flushSession();
        $this->app['auth']->forgetGuards();

        return (string) $slugDoc;
    }

    private function withTenant(string $slug, callable $callback): mixed
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        tenancy()->initialize($slug);
        $resultat = $callback();
        tenancy()->end();

        return $resultat;
    }

    public function test_reaction_toggle_et_compteurs_recalcules_cote_serveur(): void
    {
        $slug = 'rea-toggle';
        $slugDoc = $this->publier($slug, 'Statistiques descriptives');

        $this->postJson($this->apiPublic($slug, "/{$slugDoc}/reactions"), ['reaction' => 'aime'])
            ->assertOk()
            ->assertJsonPath('reaction', 'aime')
            ->assertJsonPath('nb_aimes', 1)
            ->assertJsonPath('nb_n_aimes', 0);

        // Même réaction → retrait.
        $this->postJson($this->apiPublic($slug, "/{$slugDoc}/reactions"), ['reaction' => 'aime'])
            ->assertOk()
            ->assertJsonPath('reaction', null)
            ->assertJsonPath('nb_aimes', 0);

        // Remplacement par l'opposée.
        $this->postJson($this->apiPublic($slug, "/{$slugDoc}/reactions"), ['reaction' => 'aime'])
            ->assertOk()
            ->assertJsonPath('nb_aimes', 1);
        $this->postJson($this->apiPublic($slug, "/{$slugDoc}/reactions"), ['reaction' => 'n_aime_pas'])
            ->assertOk()
            ->assertJsonPath('reaction', 'n_aime_pas')
            ->assertJsonPath('nb_aimes', 0)
            ->assertJsonPath('nb_n_aimes', 1);
    }

    public function test_reaction_invalide_rejetee(): void
    {
        $slug = 'rea-invalide';
        $slugDoc = $this->publier($slug, 'Algorithmique');

        $this->postJson($this->apiPublic($slug, "/{$slugDoc}/reactions"), ['reaction' => 'inconnue'])
            ->assertStatus(422);
    }

    public function test_reactions_de_deux_membres_sont_independantes(): void
    {
        $slug = 'rea-deux';
        $slugDoc = $this->publier($slug, 'Histoire contemporaine');

        // Membre A connecté réagit.
        $this->connecteMembre($slug, "parent@{$slug}.local", 'parent');
        $this->postJson($this->apiPublic($slug, "/{$slugDoc}/reactions"), ['reaction' => 'aime'])
            ->assertOk()
            ->assertJsonPath('nb_aimes', 1);

        // Membre B (autre session) réagit différemment.
        $this->flushSession();
        $this->connecteMembre($slug, "enseignant2@{$slug}.local", 'enseignant');
        $this->postJson($this->apiPublic($slug, "/{$slugDoc}/reactions"), ['reaction' => 'n_aime_pas'])
            ->assertOk()
            ->assertJsonPath('nb_aimes', 1)
            ->assertJsonPath('nb_n_aimes', 1);
    }

    public function test_visiteur_anonyme_une_identite_une_reaction(): void
    {
        $slug = 'rea-anon';
        $slugDoc = $this->publier($slug, 'Philosophie générale');

        $this->postJson($this->apiPublic($slug, "/{$slugDoc}/reactions"), ['reaction' => 'aime'])
            ->assertOk()
            ->assertJsonPath('nb_aimes', 1);

        // Même session (identité visiteur inchangée) : bascule.
        $this->postJson($this->apiPublic($slug, "/{$slugDoc}/reactions"), ['reaction' => 'n_aime_pas'])
            ->assertOk()
            ->assertJsonPath('nb_aimes', 0)
            ->assertJsonPath('nb_n_aimes', 1);

        $this->withTenant($slug, function () use ($slugDoc) {
            $document = DocumentBibliotheque::where('slug', $slugDoc)->firstOrFail();
            $this->assertSame(
                1,
                DocumentReaction::where('document_bibliotheque_id', $document->id)->count(),
                'Une seule réaction par identité visiteur.'
            );
        });
    }

    public function test_acces_dedupliques_sous_trente_minutes(): void
    {
        $slug = 'rea-dedup';
        $slugDoc = $this->publier($slug, 'Géométrie analytique');

        // Première vue (show) : +1.
        $this->getJson($this->apiPublic($slug, "/{$slugDoc}"))->assertOk();

        $nbVues = $this->withTenant($slug, function () use ($slugDoc) {
            return (int) DocumentBibliotheque::where('slug', $slugDoc)->value('nb_vues');
        });
        $this->assertSame(1, $nbVues, 'Première consultation comptabilisée.');

        // Deuxième vue immédiate : toujours 1 (dédup session).
        $this->getJson($this->apiPublic($slug, "/{$slugDoc}"))->assertOk();

        $nbVues = $this->withTenant($slug, function () use ($slugDoc) {
            return (int) DocumentBibliotheque::where('slug', $slugDoc)->value('nb_vues');
        });
        $this->assertSame(1, $nbVues, 'Double vue sous 30 min non comptabilisée.');

        // Téléchargement : compte une fois, indépendamment de la vue.
        $this->get($this->apiPublic($slug, "/{$slugDoc}/telecharger"))->assertOk();
        $this->get($this->apiPublic($slug, "/{$slugDoc}/telecharger"))->assertOk();

        $nbTele = $this->withTenant($slug, function () use ($slugDoc) {
            return (int) DocumentBibliotheque::where('slug', $slugDoc)->value('nb_telechargements');
        });
        $this->assertSame(1, $nbTele, 'Téléchargements dédupliqués sous 30 min.');
    }

    public function test_reactions_anonymes_transferees_au_compte_au_login(): void
    {
        $slug = 'rea-transfert';
        $slugDoc = $this->publier($slug, 'Chimie minérale');

        // Visiteur anonyme aime le document (sans compte).
        $this->postJson($this->apiPublic($slug, "/{$slugDoc}/reactions"), ['reaction' => 'aime'])
            ->assertOk()
            ->assertJsonPath('nb_aimes', 1);

        // Le membre se connecte : la réaction anonyme lui est transférée.
        $this->connecteMembre($slug, "parent@{$slug}.local", 'parent');

        // Nouvelle session : il ne re-réagit PAS (pas de doublon).
        $this->getJson($this->apiPublic($slug, "/{$slugDoc}"))
            ->assertOk()
            ->assertJsonPath('ma_reaction', 'aime')
            ->assertJsonPath('nb_aimes', 1);

        $this->withTenant($slug, function () use ($slugDoc) {
            $document = DocumentBibliotheque::where('slug', $slugDoc)->firstOrFail();
            $this->assertSame(
                1,
                DocumentReaction::where('document_bibliotheque_id', $document->id)->count(),
                'Aucun doublon après transfert anonyme → membre.'
            );
            $this->assertSame(
                'u',
                substr(DocumentReaction::where('document_bibliotheque_id', $document->id)->value('identifiant'), 0, 1),
                'L\'identité est devenue celle du membre (préfixe u:).'
            );
        });
    }
}
