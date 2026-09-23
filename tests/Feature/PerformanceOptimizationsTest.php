<?php

namespace Tests\Feature;

use App\Models\CategorieProduit;
use App\Models\Commande;
use App\Models\DocumentBibliotheque;
use App\Models\FactureCabinet;
use App\Models\LigneCommande;
use App\Models\Notification;
use App\Models\PaiementCabinet;
use App\Models\Produit;
use App\Models\TypeDocument;
use App\Models\User;
use App\Modules\Bibliotheque\Services\DocumentBibliothequeService;
use App\Modules\Librairie\Services\LibrairieService;
use App\Modules\Pedagogie\Services\DemandeCoursService;
use Database\Seeders\ClasseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypeCoursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Partie 05 — Optimisation des performances :
 * vérifie que les corrections anti N+1 et anti requêtes répétées
 * sont en place (eager loading, withCount/withSum, composer restreint).
 */
class PerformanceOptimizationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::create([
            'nom' => 'Nom',
            'prenom' => 'Prenom',
            'email' => strtolower($role) . '_' . uniqid() . '@example.com',
            'password' => 'password',
        ]);

        $user->assignRole($role);

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | FactureCabinet : montant_paye / montant_restant sans N+1
    |--------------------------------------------------------------------------
    */

    public function test_facture_cabinet_index_withsum_ne_declenche_pas_de_requete_supplementaire(): void
    {
        $facture = FactureCabinet::create([
            'numero' => 'FCAB-TEST-00001',
            'periode_debut' => '2026-08-01',
            'periode_fin' => '2026-08-31',
            'montant_total_du' => 5000,
            'statut' => 'en_attente',
            'date_facture' => '2026-08-14',
        ]);

        PaiementCabinet::create([
            'facture_cabinet_id' => $facture->id,
            'montant_paye' => 2000,
            'mode_paiement' => 'mobile',
            'date_paiement' => '2026-08-14',
            'statut' => 'valide',
        ]);

        PaiementCabinet::create([
            'facture_cabinet_id' => $facture->id,
            'montant_paye' => 1000,
            'mode_paiement' => 'mobile',
            'date_paiement' => '2026-08-14',
            'statut' => 'annule',
        ]);

        $factures = FactureCabinet::query()
            ->withSum(
                ['paiements as montant_paye' => fn ($q) => $q->where('statut', 'valide')],
                'montant_paye'
            )
            ->get();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->assertSame(2000, $factures->first()->montant_paye);
        $this->assertSame(3000, $factures->first()->montant_restant);
        $this->assertSame(
            0,
            $queries,
            'L accès à montant_paye / montant_restant ne doit déclencher aucune requête SQL.'
        );
    }

    public function test_facture_cabinet_accessor_utilise_la_collection_chargee(): void
    {
        $facture = FactureCabinet::create([
            'numero' => 'FCAB-TEST-00002',
            'periode_debut' => '2026-08-01',
            'periode_fin' => '2026-08-31',
            'montant_total_du' => 5000,
            'statut' => 'en_attente',
            'date_facture' => '2026-08-14',
        ]);

        PaiementCabinet::create([
            'facture_cabinet_id' => $facture->id,
            'montant_paye' => 1500,
            'mode_paiement' => 'especes',
            'date_paiement' => '2026-08-14',
            'statut' => 'valide',
        ]);

        $facture = FactureCabinet::with('paiements')->first();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->assertSame(1500, $facture->montant_paye);
        $this->assertSame(0, $queries);
    }

    public function test_facture_cabinet_accessor_conserve_le_fallback_requete(): void
    {
        $facture = FactureCabinet::create([
            'numero' => 'FCAB-TEST-00003',
            'periode_debut' => '2026-08-01',
            'periode_fin' => '2026-08-31',
            'montant_total_du' => 5000,
            'statut' => 'en_attente',
            'date_facture' => '2026-08-14',
        ]);

        PaiementCabinet::create([
            'facture_cabinet_id' => $facture->id,
            'montant_paye' => 900,
            'mode_paiement' => 'mobile',
            'date_paiement' => '2026-08-14',
            'statut' => 'valide',
        ]);

        $this->assertSame(900, $facture->fresh()->montant_paye);
        $this->assertSame(4100, $facture->fresh()->montant_restant);
    }

    /*
    |--------------------------------------------------------------------------
    | Librairie : eager loading commandes.user / topProduits.media
    |--------------------------------------------------------------------------
    */

    public function test_paginate_commandes_charge_la_relation_user(): void
    {
        $admin = $this->createUserWithRole('admin');

        Commande::create([
            'user_id' => $admin->id,
            'nom_client' => 'Client 1',
            'telephone_client' => '0102030405',
            'adresse_livraison' => 'Yaoundé',
            'montant_total' => 5000,
            'statut' => Commande::STATUT_EN_ATTENTE,
            'token' => 'token-test-1',
        ]);

        Commande::create([
            'user_id' => $admin->id,
            'nom_client' => 'Client 2',
            'telephone_client' => '0102030406',
            'adresse_livraison' => 'Douala',
            'montant_total' => 7500,
            'statut' => Commande::STATUT_EN_ATTENTE,
            'token' => 'token-test-2',
        ]);

        $commandes = (new LibrairieService())->paginateCommandes();

        $this->assertCount(2, $commandes);
        foreach ($commandes as $commande) {
            $this->assertTrue(
                $commande->relationLoaded('user'),
                'La relation user doit être chargée pour éviter le N+1 sur l index.'
            );
        }
    }

    public function test_dashboard_stats_charge_la_media_des_top_produits(): void
    {
        $admin = $this->createUserWithRole('admin');

        $commande = Commande::create([
            'user_id' => $admin->id,
            'nom_client' => 'Client 1',
            'telephone_client' => '0102030405',
            'adresse_livraison' => 'Yaoundé',
            'montant_total' => 2000,
            'statut' => Commande::STATUT_CONFIRMEE,
            'token' => 'token-dashboard',
        ]);

        $categorie = CategorieProduit::create([
            'nom' => 'Livres',
            'slug' => 'livres',
            'is_active' => true,
        ]);

        $produit = Produit::create([
            'categorie_id' => $categorie->id,
            'nom' => 'Manuel de maths',
            'slug' => 'manuel-de-maths',
            'prix' => 2000,
            'is_active' => true,
        ]);

        LigneCommande::create([
            'commande_id' => $commande->id,
            'produit_id' => $produit->id,
            'quantite' => 1,
            'prix_unitaire' => 2000,
            'sous_total' => 2000,
        ]);

        $stats = (new LibrairieService())->getDashboardStats();

        $this->assertNotEmpty($stats['topProduits']);
        foreach ($stats['topProduits'] as $top) {
            $this->assertTrue(
                $top->relationLoaded('media'),
                'La relation media doit être chargée pour éviter le N+1 sur image_url.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Bibliothèque : eager loading media sur les listes
    |--------------------------------------------------------------------------
    */

    public function test_paginate_for_user_charge_la_relation_media(): void
    {
        $user = $this->createUserWithRole('enseignant');
        $typeDocument = TypeDocument::create(['nom' => 'Cours', 'sigle' => 'C']);

        DocumentBibliotheque::create([
            'user_id' => $user->id,
            'titre' => 'Cours de maths',
            'type_document_id' => $typeDocument->id,
            'is_public' => false,
            'statut' => 'publie',
        ]);

        $documents = (new DocumentBibliothequeService())->paginateForUser($user->id);

        $this->assertNotEmpty($documents);
        foreach ($documents as $document) {
            $this->assertTrue(
                $document->relationLoaded('media'),
                'La relation media doit être chargée pour éviter le N+1 sur getFirstMedia.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Demande de cours : chaque admin notifié une seule fois (anti doublon A×A)
    |--------------------------------------------------------------------------
    */

    public function test_demande_cours_notifie_chaque_admin_une_fois(): void
    {
        $this->seed([TypeCoursSeeder::class, ClasseSeeder::class]);

        $this->createUserWithRole('admin');
        $this->createUserWithRole('admin');

        (new DemandeCoursService())->create([
            'nom_parent' => 'Parent Test',
            'prenom_parent' => 'Prenom',
            'telephone' => '0102030405',
            'type_cours_id' => \App\Models\TypeCours::first()->id,
            'classe_id' => \App\Models\Classe::first()->id,
            'volume_horaire_estime' => 2,
            'message' => null,
        ]);

        $this->assertSame(
            2,
            Notification::count(),
            'Chaque admin doit recevoir une notification unique (et non une par admin × une par itération).'
        );
    }
}
