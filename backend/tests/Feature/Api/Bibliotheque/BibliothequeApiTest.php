<?php

namespace Tests\Feature\Api\Bibliotheque;

use App\Models\Cabinet;
use App\Models\DocumentBibliotheque;
use App\Models\Eleve;
use App\Models\TypeDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API bibliothèque — espace membre + modération (écran Angular).
 *
 * Verrouille le parcours complet :
 *  - un enseignant dépose un document (upload FormData, statut en_attente) ;
 *  - le document non publié est invisible des autres membres (403) ;
 *  - favoris (toggle avec compteur), commentaires imbriqués, notes ;
 *  - signalement (plusieurs signalements possibles, auteur exclu) ;
 *  - l'admin modère : liste + compteurs, valider, signalements, traiter ;
 *  - téléchargement contrôlé qui compte les accès ;
 *  - 401 visiteur, 403 enseignant sur la modération.
 */
class BibliothequeApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    /** Crée (si besoin) le cabinet et un compte membre dédié, puis connecte. */
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
            'prenom' => 'Biblio',
            'email' => $email,
            'password' => Hash::make('Secret1234'),
        ]);
        $user->assignRole($role);
        // L'admin seedé a un mot de passe aléatoire : on force le nôtre.
        $user->update(['password' => Hash::make('Secret1234')]);
        // La connexion d'un élève exige un profil actif (`AuthService`) ;
        // `eleves.parent_id` et `eleves.classe_id` sont NOT NULL : un parent
        // de rattachement et une classe sont requis.
        if ($role === 'eleve') {
            $parent = User::where('email', "parent@{$slug}.local")->first() ?? User::create([
                'nom' => 'Membre',
                'prenom' => 'Parent',
                'email' => "parent@{$slug}.local",
                'password' => Hash::make('Secret1234'),
            ]);
            $parent->assignRole('parent');
            $parent->parentProfil()->firstOrCreate([], []);

            $classe = \App\Models\Classe::firstOrCreate(
                ['nom' => '6e A'],
                ['sigle' => '6A']
            );

            Eleve::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'parent_id' => $parent->id,
                    'classe_id' => $classe->id,
                    'statut' => true,
                ]
            );
        }
        if ($role === 'parent') {
            $user->parentProfil()->firstOrCreate([], []);
        }
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function connecteAdmin(string $slug): void
    {
        $this->connecteMembre($slug, "admin@{$slug}.local", 'admin_cabinet');
    }

    private function api(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/bibliotheque{$chemin}";
    }

    private function adminApi(string $slug, string $chemin = ''): string
    {
        return "http://${slug}.localhost/api/admin/bibliotheque{$chemin}";
    }

    private function assertErreur(TestResponse $reponse, string $champ): void
    {
        $reponse->assertStatus(422);
        $this->assertSame('VALIDATION', $reponse->json('code'));
        $this->assertNotEmpty($reponse->json("erreurs.{$champ}"));
    }

    /** Premier type de document du cabinet (seedé par TypeDocumentSeeder). */
    private function typeId(string $slug): int
    {
        tenancy()->initialize($slug);
        $id = TypeDocument::query()->value('id');
        tenancy()->end();

        return (int) $id;
    }

    /** Premier document du cabinet (helper de lecture). */
    private function premierDocumentId(string $slug): int
    {
        tenancy()->initialize($slug);
        $id = DocumentBibliotheque::query()->value('id');
        tenancy()->end();

        return (int) $id;
    }

    /**
     * Dépose un document en tant que connecté et renvoie la réponse 201.
     */
    private function deposer(string $slug, string $titre, string $statut = 'en_attente'): TestResponse
    {
        return $this
            ->withHeaders(['Accept' => 'application/json'])
            ->post($this->api($slug, '/documents'), [
                'titre' => $titre,
                'type_document_id' => $this->typeId($slug),
                'statut' => $statut,
                'is_public' => '1',
                'resume' => 'Résumé du support de cours.',
                'fichier' => UploadedFile::fake()->create('support-cours.pdf', 40, 'application/pdf'),
            ]);
    }

    /**
     * Crée le cabinet, un enseignant, connecte et dépose ; renvoie la réponse.
     */
    private function deposerCommeEnseignant(string $slug, string $titre, string $statut = 'en_attente'): TestResponse
    {
        $this->connecteMembre($slug, "enseignant@{$slug}.local", 'enseignant');

        return $this->deposer($slug, $titre, $statut);
    }

    /** Dépose puis fait valider le document par l'admin ; renvoie son id. */
    private function publierCommeEnseignant(string $slug, string $titre): int
    {
        $id = $this->deposerCommeEnseignant($slug, $titre)->json('data.id');

        $this->connecteAdmin($slug);
        $this->patchJson($this->adminApi($slug, "/{$id}/valider"))
            ->assertOk()
            ->assertJsonPath('data.statut', 'publie');

        return (int) $id;
    }

    public function test_enseignant_depose_un_document_et_le_voit_dans_mes_documents(): void
    {
        $slug = 'bib-depot';
        $this->connecteMembre($slug, "enseignant@{$slug}.local", 'enseignant');

        $reponse = $this->deposer($slug, 'Fonctions dérivées');
        $reponse->assertStatus(201);
        $this->assertSame('Fonctions dérivées', $reponse->json('data.titre'));
        $this->assertSame('en_attente', $reponse->json('data.statut'));
        $this->assertSame('support-cours.pdf', $reponse->json('data.fichier.nom'));
        $this->assertNotNull($reponse->json('data.type_document.id'));

        $this->getJson($this->api($slug, '/mes-documents'))
            ->assertOk()
            ->assertJsonPath('data.0.titre', 'Fonctions dérivées');
    }

    public function test_document_non_publie_est_invisible_des_autres_membres(): void
    {
        $slug = 'bib-prive';
        $this->deposerCommeEnseignant($slug, 'Notes secrètes', 'brouillon');

        $this->connecteMembre($slug, "parent@{$slug}.local", 'parent');

        $this->getJson($this->api($slug, '/documents/' . $this->premierDocumentId($slug)))
            ->assertStatus(403);
    }

    public function test_favori_toggle_avec_compteur(): void
    {
        $slug = 'bib-favori';
        $id = $this->publierCommeEnseignant($slug, 'Chimie organique');

        $this->connecteMembre($slug, "parent@{$slug}.local", 'parent');

        $this->postJson($this->api($slug, "/documents/{$id}/favori"))
            ->assertOk()
            ->assertJsonPath('data.is_favori', true)
            ->assertJsonPath('data.nombre_favoris', 1);

        $this->getJson($this->api($slug, '/favoris'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->postJson($this->api($slug, "/documents/{$id}/favori"))
            ->assertOk()
            ->assertJsonPath('data.is_favori', false)
            ->assertJsonPath('data.nombre_favoris', 0);
    }

    public function test_commentaire_et_reponse_sont_imbriques(): void
    {
        $slug = 'bib-commentaires';
        $id = $this->publierCommeEnseignant($slug, 'Méthodologie dissertation');

        $this->connecteMembre($slug, "parent@{$slug}.local", 'parent');

        $parent = $this->postJson($this->api($slug, "/documents/{$id}/commentaires"), [
            'contenu' => 'Merci pour ce support !',
        ]);
        $parent->assertStatus(201);

        $this->postJson($this->api($slug, "/documents/{$id}/commentaires"), [
            'contenu' => 'De rien, bonne révision.',
            'parent_id' => $parent->json('data.id'),
        ])->assertStatus(201);

        $this->getJson($this->api($slug, "/documents/{$id}"))
            ->assertOk()
            ->assertJsonPath('data.commentaires.0.contenu', 'Merci pour ce support !')
            ->assertJsonPath('data.commentaires.0.replies.0.contenu', 'De rien, bonne révision.');
    }

    public function test_noter_un_document_recalcule_la_moyenne(): void
    {
        $slug = 'bib-notes';
        $id = $this->publierCommeEnseignant($slug, 'Exercices type bac');

        $this->connecteMembre($slug, "eleve@{$slug}.local", 'eleve');

        $this->postJson($this->api($slug, "/documents/{$id}/notes"), ['note' => 4])
            ->assertOk()
            ->assertJsonPath('data.ma_note', 4)
            ->assertJsonPath('data.note_moyenne', 4)
            ->assertJsonPath('data.nb_notes', 1);

        // Re-noter met à jour la note de l'utilisateur, pas la moyenne à deux.
        $this->postJson($this->api($slug, "/documents/{$id}/notes"), ['note' => 5])
            ->assertOk()
            ->assertJsonPath('data.note_moyenne', 5)
            ->assertJsonPath('data.nb_notes', 1);
    }

    public function test_signalement_et_garde_fou_auteur(): void
    {
        $slug = 'bib-signalement';
        $id = $this->publierCommeEnseignant($slug, 'Contenu discutable');

        $this->connecteMembre($slug, "parent@{$slug}.local", 'parent');

        $this->postJson($this->api($slug, "/documents/{$id}/signalements"), [
            'motif' => 'contenu_inapproprie',
            'description' => 'Cette fiche contient des erreurs.',
            'precisions' => 'Page 2, exercice 3.',
        ])->assertStatus(201);

        // Plusieurs signalements par le même membre sont acceptés.
        $this->postJson($this->api($slug, "/documents/{$id}/signalements"), [
            'motif' => 'doublon',
        ])->assertStatus(201);

        // Motif hors référentiel → validation 422.
        $this->connecteMembre($slug, "parent@{$slug}.local", 'parent');
        // Déconnecte pour tester un autre membre avec motif invalide.
        $this->postJson("http://{$slug}.localhost/api/auth/deconnexion")->assertOk();
        $this->connecteMembre($slug, "eleve@{$slug}.local", 'eleve');
        $this->postJson($this->api($slug, "/documents/{$id}/signalements"), [
            'motif' => 'Auto-signalement libre',
        ])->assertStatus(422);

        // L'auteur ne peut pas signaler son propre document.
        $this->postJson("http://{$slug}.localhost/api/auth/deconnexion")->assertOk();
        $this->connecteMembre($slug, "enseignant@{$slug}.local", 'enseignant');
        $this->postJson($this->api($slug, "/documents/{$id}/signalements"), [
            'motif' => 'autre',
        ])->assertStatus(403);
    }

    public function test_auteur_met_a_jour_et_supprime_son_document(): void
    {
        $slug = 'bib-auteur';
        $this->deposerCommeEnseignant($slug, 'Version brouillon', 'brouillon');

        $documentId = $this->premierDocumentId($slug);

        $this->withHeaders(['Accept' => 'application/json'])
            ->post($this->api($slug, "/documents/{$documentId}"), [
                'titre' => 'Version corrigée',
            ])
            ->assertOk()
            ->assertJsonPath('data.titre', 'Version corrigée');

        $this->deleteJson($this->api($slug, "/documents/{$documentId}"))
            ->assertOk();

        $this->getJson($this->api($slug, '/mes-documents'))
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_moderation_valide_liste_et_traite_les_signalements(): void
    {
        $slug = 'bib-mod';
        $id = $this->deposerCommeEnseignant($slug, 'À modérer', 'en_attente')->json('data.id');

        // Un membre signale pendant que le document est encore en attente.
        $this->connecteMembre($slug, "parent@{$slug}.local", 'parent');
        $this->postJson($this->api($slug, "/documents/{$id}/signalements"), [
            'motif' => 'document_incorrect',
            'description' => 'Énoncé faux.',
        ])->assertStatus(201);

        $this->connecteAdmin($slug);

        $liste = $this->getJson($this->adminApi($slug));
        $liste->assertOk();
        $this->assertArrayHasKey('stats', $liste->json('meta'));
        $this->assertSame('À modérer', $liste->json('data.0.titre'));

        $this->patchJson($this->adminApi($slug, "/{$id}/valider"))
            ->assertOk()
            ->assertJsonPath('data.statut', 'publie');

        $signalements = $this->getJson($this->adminApi($slug, '/signalements'));
        $signalements->assertOk();
        $this->assertSame('document_incorrect', $signalements->json('data.0.motif'));
        $this->assertSame($id, $signalements->json('data.0.document.id'));

        $sid = $signalements->json('data.0.id');

        // Filtres : motif + statut.
        $this->getJson($this->adminApi($slug, '/signalements?motif=document_incorrect'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $sid);
        $this->getJson($this->adminApi($slug, '/signalements?motif=doublon'))
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        // Mise à jour enrichie : statut en_cours + note admin.
        $this->patchJson($this->adminApi($slug, "/signalements/{$sid}"), [
            'statut' => 'en_cours',
            'note_admin' => 'Vérification en cours avec l\'auteur.',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'en_cours')
            ->assertJsonPath('data.note_admin', 'Vérification en cours avec l\'auteur.');

        $this->patchJson($this->adminApi($slug, "/signalements/{$sid}/traiter"))
            ->assertOk()
            ->assertJsonPath('data.statut', 'traite');

        // Après traitement, la file (sans filtre) garde le signalement et
        // trace l'action effectuée (statut, date, modérateur) + compteurs.
        $apresTraitement = $this->getJson($this->adminApi($slug, '/signalements'));
        $apresTraitement->assertOk()
            ->assertJsonPath('data.0.id', $sid)
            ->assertJsonPath('data.0.statut', 'traite')
            ->assertJsonPath('data.0.statut_libelle', 'Traité');
        $this->assertNotNull($apresTraitement->json('data.0.traite_at'));
        $this->assertSame(1, $apresTraitement->json('meta.stats.traite'));

        // Détail de modération : signalements chargés sur le document avec
        // la trace de l'action.
        $this->getJson($this->adminApi($slug, "/{$id}"))
            ->assertOk()
            ->assertJsonPath('data.statut', 'publie')
            ->assertJsonPath('data.signalements.0.statut', 'traite')
            ->assertJsonPath('data.signalements.0.statut_libelle', 'Traité');
        $this->assertNotNull($this->getJson($this->adminApi($slug, "/{$id}"))
            ->json('data.signalements.0.traite_at'));
    }

    public function test_signalement_public_anonyme_avec_protections(): void
    {
        $slug = 'bib-signal-public';
        $id = $this->publierCommeEnseignant($slug, 'Fiche publique signalable');

        // On se déconnecte pour simuler un visiteur anonyme.
        $this->postJson("http://{$slug}.localhost/api/auth/deconnexion")->assertOk();

        $this->postJson("http://{$slug}.localhost/api/public/documents/fiche-publique-signalable/signalements", [
            'motif' => 'fichier_illisible',
            'description' => 'Le PDF ne s\'ouvre pas.',
            'precisions' => 'Testé sur Chrome et Firefox.',
            'email_contact' => 'visiteur@exemple.com',
        ])->assertStatus(201)
            ->assertJsonPath('data.motif', 'fichier_illisible')
            ->assertJsonPath('data.statut', 'en_attente');

        // Plusieurs signalements anonymes sont acceptés (throttle côté route).
        $this->postJson("http://{$slug}.localhost/api/public/documents/fiche-publique-signalable/signalements", [
            'motif' => 'doublon',
        ])->assertStatus(201)
            ->assertJsonPath('data.statut', 'en_attente');

        // Le document non publié n'est pas signalable via l'URL publique.
        $this->connecteMembre($slug, "enseignant2@{$slug}.local", 'enseignant');
        $this->deposer($slug, 'Brouillon non public', 'brouillon');
        $this->postJson("http://{$slug}.localhost/api/public/documents/brouillon-non-public/signalements", [
            'motif' => 'autre',
        ])->assertNotFound();

        // Un signalement n'entraîne pas le retrait automatique du document.
        $this->postJson("http://{$slug}.localhost/api/auth/deconnexion")->assertOk();
        $this->getJson("http://{$slug}.localhost/api/public/documents/fiche-publique-signalable")
            ->assertOk()
            ->assertJsonPath('id', $id);
    }

    public function test_signalement_public_rejecte_motif_invalide(): void
    {
        $slug = 'bib-signal-motif';
        $this->publierCommeEnseignant($slug, 'Document test motif');

        $this->postJson("http://{$slug}.localhost/api/public/documents/document-test-motif/signalements", [
            'motif' => 'nimporte quoi',
        ])->assertStatus(422);

        $this->postJson("http://{$slug}.localhost/api/public/documents/document-test-motif/signalements", [
            'motif' => '',
        ])->assertStatus(422);
    }

    public function test_telechargement_compte_les_acces(): void
    {
        $slug = 'bib-telechargement';
        $this->publierCommeEnseignant($slug, 'Support téléchargeable');

        $this->connecteMembre($slug, "eleve@{$slug}.local", 'eleve');

        $documentId = $this->premierDocumentId($slug);

        $this->get($this->api($slug, "/telecharger/{$documentId}"))
            ->assertOk();

        $this->getJson($this->api($slug, "/documents/{$documentId}"))
            ->assertOk()
            ->assertJsonPath('data.nb_telechargements', 1);
    }

    public function test_referentiels_du_formulaire(): void
    {
        $slug = 'bib-referentiels';
        $this->connecteMembre($slug, "enseignant@{$slug}.local", 'enseignant');

        $reponse = $this->getJson($this->api($slug, '/referentiels'));
        $reponse->assertOk();
        $this->assertNotEmpty($reponse->json('data.types'));
    }

    public function test_dashboard_renvoie_stats_et_derniers_commentaires(): void
    {
        $slug = 'bib-dashboard';
        $id = $this->publierCommeEnseignant($slug, 'Document suivi');

        $this->connecteMembre($slug, "parent@{$slug}.local", 'parent');
        $this->postJson($this->api($slug, "/documents/{$id}/commentaires"), [
            'contenu' => 'Très utile, merci.',
        ])->assertStatus(201);

        $this->connecteMembre($slug, "enseignant@{$slug}.local", 'enseignant');

        $reponse = $this->getJson($this->api($slug, '/dashboard'));
        $reponse->assertOk();
        $this->assertSame(1, $reponse->json('data.total'));
        $this->assertSame(1, $reponse->json('data.publies'));
        $this->assertSame('Très utile, merci.', $reponse->json('data.derniers_commentaires.0.contenu'));
    }

    public function test_enseignant_recu_403_sur_la_moderation(): void
    {
        $slug = 'bib-403';
        $this->connecteMembre($slug, "enseignant@{$slug}.local", 'enseignant');

        $this->getJson($this->adminApi($slug))->assertStatus(403);
    }

    public function test_visiteur_recu_401(): void
    {
        $slug = 'bib-401';
        $this->makeCabinet($slug);

        $this->getJson($this->api($slug, '/dashboard'))->assertStatus(401);
        $this->getJson($this->api($slug, '/mes-documents'))->assertStatus(401);
        $this->getJson($this->adminApi($slug))->assertStatus(401);
    }

    public function test_validation_fichier_obligatoire_a_la_creation(): void
    {
        $slug = 'bib-validation';
        $this->connecteMembre($slug, "enseignant@{$slug}.local", 'enseignant');

        $this->assertErreur(
            $this->postJson($this->api($slug, '/documents'), [
                'titre' => 'Sans fichier',
                'type_document_id' => $this->typeId($slug),
            ]),
            'fichier'
        );
    }
}
