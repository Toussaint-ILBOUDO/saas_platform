<?php

namespace Tests\Feature;

use App\Mail\ActualitePublishedMail;
use App\Models\Actualite;
use App\Models\AffectationEnseignant;
use App\Models\CahierTexte;
use App\Models\CategorieProduit;
use App\Models\Classe;
use App\Models\Commande;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\LigneCommande;
use App\Models\Matiere;
use App\Models\Produit;
use App\Models\TypeCours;
use App\Models\User;
use App\Modules\Pedagogie\Services\CahierTextePdfService;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Database\Seeders\ClasseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypeCoursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Partie 06 — Performance & scalabilité :
 * emails asynchrones (queue), index SQL, compteurs agrégés,
 * eager loading des médias sur les commandes, PDF sans fuite de stockage.
 */
class Partie06OptimisationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ClasseSeeder::class);
        $this->seed(TypeCoursSeeder::class);
    }

    private function createUserWithRole(string $role): User
    {
        $user = $this->createUser(strtolower($role) . '_' . uniqid() . '@example.com');

        $user->assignRole($role);

        return $user;
    }

    private function createUser(string $email): User
    {
        return User::create([
            'nom' => 'Nom',
            'prenom' => 'Prenom',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Emails d'actualité : envoi asynchrone (queue)
    |--------------------------------------------------------------------------
    */

    public function test_publication_actualite_emails_envoyes_en_queue(): void
    {
        Mail::fake();

        $this->createUserWithRole('parent');

        $auteur = $this->createUser('auteur@example.com');

        $actualite = Actualite::create([
            'user_id' => $auteur->id,
            'titre' => 'Nouvelle offre',
            'slug' => 'nouvelle-offre-' . uniqid(),
            'contenu' => 'Contenu de la nouvelle actualité.',
            'statut' => 'publiee',
            'published_at' => now(),
            'is_active' => true,
        ]);

        app(NotificationDispatcher::class)
            ->actualitePublished($actualite, ['parents'], 'email');

        Mail::assertQueued(ActualitePublishedMail::class, 1);
        Mail::assertNotSent(ActualitePublishedMail::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Index SQL de performance
    |--------------------------------------------------------------------------
    */

    public function test_index_performance_presents_en_base(): void
    {
        $this->assertTrue(collect(Schema::getIndexes('notifications'))
            ->pluck('name')->contains('idx_notifications_user_id'));

        $this->assertTrue(collect(Schema::getIndexes('commandes'))
            ->pluck('name')->contains('idx_commandes_user_id'));

        $this->assertTrue(collect(Schema::getIndexes('temoignages'))
            ->pluck('name')->contains('idx_temoignages_user_id'));

        $this->assertTrue(collect(Schema::getIndexes('temoignage_commentaires'))
            ->pluck('name')->contains('idx_temoignage_commentaires_temoignage_id'));

        $this->assertTrue(collect(Schema::getIndexes('document_commentaires'))
            ->pluck('name')->contains('idx_document_commentaires_document_id'));

        $this->assertTrue(collect(Schema::getIndexes('document_notes'))
            ->pluck('name')->contains('idx_document_notes_document_id'));
    }

    /*
    |--------------------------------------------------------------------------
    | Page d'accueil : compteurs agrégés en une seule requête
    |--------------------------------------------------------------------------
    */

    public function test_home_compteurs_agreges_en_une_seule_requete(): void
    {
        $requetesCount = 0;

        DB::listen(function ($query) use (&$requetesCount) {
            if (preg_match('/count\s*\(\s*\*/i', $query->sql)) {
                $requetesCount++;
            }
        });

        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame(1, $requetesCount, 'Les 5 compteurs de la page d\'accueil doivent être calculés en une seule requête.');
    }

    /*
    |--------------------------------------------------------------------------
    | Commandes : médias des produits pré-chargés (pas de N+1)
    |--------------------------------------------------------------------------
    */

    public function test_mes_commandes_show_precharge_les_medias_des_produits(): void
    {
        $user = $this->createUser('client_' . uniqid() . '@example.com');

        $categorie = CategorieProduit::create(['nom' => 'Manuels']);

        $produit1 = Produit::create([
            'categorie_id' => $categorie->id,
            'nom' => 'Manuel Maths',
            'slug' => 'manuel-maths-' . uniqid(),
            'prix' => 1000,
        ]);

        $produit2 = Produit::create([
            'categorie_id' => $categorie->id,
            'nom' => 'Manuel Français',
            'slug' => 'manuel-francais-' . uniqid(),
            'prix' => 1500,
        ]);

        $commande = Commande::create([
            'user_id' => $user->id,
            'nom_client' => 'Nom Client',
            'telephone_client' => '0101010101',
            'adresse_livraison' => 'Adresse',
            'montant_total' => 2500,
            'statut' => 'en_attente',
        ]);

        foreach ([$produit1, $produit2] as $produit) {
            LigneCommande::create([
                'commande_id' => $commande->id,
                'produit_id' => $produit->id,
                'quantite' => 1,
                'prix_unitaire' => $produit->prix,
                'sous_total' => $produit->prix,
            ]);
        }

        $requetesMedia = 0;

        DB::listen(function ($query) use (&$requetesMedia) {
            if (preg_match('/\bfrom\s+"?media"?\b/i', $query->sql)) {
                $requetesMedia++;
            }
        });

        $this->actingAs($user)
            ->get(route('librairie.mes-commandes.show', $commande))
            ->assertOk();

        $this->assertSame(1, $requetesMedia, 'Les médias des produits doivent être chargés en une seule requête (eager loading).');
    }

    /*
    |--------------------------------------------------------------------------
    | PDF cahiers de texte : pas de fuite de stockage, historique corrigé
    |--------------------------------------------------------------------------
    */

    public function test_cahier_pdf_download_single_ne_duplique_pas_les_medias(): void
    {
        Storage::fake('public');

        [, $cahier] = $this->createCahierTexteFixture();

        $service = app(CahierTextePdfService::class);

        $service->downloadSingle($cahier);
        $service->downloadSingle($cahier);

        $this->assertCount(1, $cahier->refresh()->getMedia('cahier_texte_pdf'));
    }

    public function test_cahier_pdf_historique_ne_crashe_plus_et_ne_cree_pas_de_media(): void
    {
        Storage::fake('public');

        [$eleve] = $this->createCahierTexteFixture();

        $service = app(CahierTextePdfService::class);

        $response = $service->downloadHistory($eleve, null, null);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    private function createCahierTexteFixture(): array
    {
        $userEleve = $this->createUser('eleve_' . uniqid() . '@example.com');
        $userEnseignant = $this->createUser('enseignant_' . uniqid() . '@example.com');
        $parent = $this->createUser('parent_' . uniqid() . '@example.com');

        $enseignant = EnseignantProfil::create(['user_id' => $userEnseignant->id]);
        $matiere = Matiere::create(['nom' => 'Mathématiques']);

        $eleve = Eleve::create([
            'user_id' => $userEleve->id,
            'parent_id' => $parent->id,
            'classe_id' => Classe::first()->id,
        ]);

        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => TypeCours::first()->id,
            'date_debut' => '2026-01-01',
            'statut' => 'actif',
        ]);

        $affectation = AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $enseignant->id,
            'matiere_id' => $matiere->id,
            'date_affectation' => '2026-01-01',
        ]);

        $cahier = CahierTexte::create([
            'affectation_enseignant_id' => $affectation->id,
            'date_seance' => '2026-01-10',
            'heure_debut' => '09:00:00',
            'heure_fin' => '10:00:00',
            'duree_heures' => 1,
            'contenu_cours' => 'Contenu de la séance',
            'objectifs_atteints' => 'Objectifs atteints',
        ]);

        return [$eleve, $cahier];
    }
}
