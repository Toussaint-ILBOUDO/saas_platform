<?php

namespace Tests\Feature;

use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\Facture;
use App\Models\PeriodeComptable;
use App\Models\TypeCommission;
use App\Models\TypeCours;
use App\Models\User;
use Database\Seeders\ClasseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypeCoursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Partie 08 — Validation globale :
 * - FactureCabinetService : requête sur colonnes inexistantes corrigée (B1)
 *   et commission « Vente » sans facteur ×100 parasite (B2).
 * - Routes mortes retirées (destroy eleves/enseignants/parents,
 *   create/store bulletins-paie).
 * - Coordonnées publiques (navbar/footer/JSON-LD) issues de config('keduc.cabinet').
 */
class Partie08ValidationGlobaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ClasseSeeder::class);
        $this->seed(TypeCoursSeeder::class);
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

    public function test_preview_facture_cabinet_sans_erreur_sql_et_commission_vente_correcte(): void
    {
        $admin = $this->createUser('admin_p08@example.com');
        $admin->assignRole('admin');
        $this->actingAs($admin);

        TypeCommission::insert([
            ['nom_du_type' => 'Cours'],
            ['nom_du_type' => 'Inscription'],
            ['nom_du_type' => 'Vente'],
        ]);

        $periode = PeriodeComptable::create([
            'label'      => 'Janvier 2026',
            'date_debut' => '2026-01-01',
            'date_fin'   => '2026-01-31',
            'type'       => 'mensuel',
            'statut'     => 'ouverte',
        ]);

        $parent = $this->createUser('parent_p08@example.com');
        $eleveUser = $this->createUser('eleve_p08@example.com');
        $classe = Classe::firstOrCreate(['nom' => 'Terminale', 'sigle' => 'Tle']);

        $eleve = Eleve::create([
            'user_id'   => $eleveUser->id,
            'parent_id' => $parent->id,
            'classe_id' => $classe->id,
        ]);

        $contrat = ContratCours::create([
            'eleve_id'      => $eleve->id,
            'type_cours_id' => TypeCours::first()->id,
            'statut'        => 'actif',
            'date_debut'    => '2025-12-01',
            'date_fin'      => null,
        ]);

        foreach (['2026-01-05', '2026-01-15'] as $i => $datePaiement) {
            Facture::create([
                'contrat_cours_id' => $contrat->id,
                'parent_id'        => $parent->id,
                'eleve_id'         => $eleve->id,
                'periode_id'       => $periode->id,
                'numero_facture'   => 'FACT-P08-' . $i,
                'montant_total'    => 50000,
                'statut_paiement'  => 'payee',
                'date_paiement'    => $datePaiement,
                'mode_paiement'    => 'especes',
            ]);
        }

        $response = $this->get(
            route('finance.facture-cabinet.preview') .
            '?date_debut=2026-01-01&date_fin=2026-01-31&taux_vente=10'
        );

        $response->assertOk();

        $data = $response->json();

        $ligneVente = collect($data['lignes'])->firstWhere('type_nom', 'Vente');

        $this->assertNotNull($ligneVente, 'La ligne Vente doit être présente dans le preview.');
        $this->assertSame(2, $ligneVente['quantite']);
        $this->assertSame(20, $ligneVente['montant'], 'Commission Vente = 2 ventes × 10, sans facteur ×100.');
        $this->assertSame(20, $data['montant_total_du'] - $data['montant_commission']);
    }

    public function test_routes_mortes_utilisateurs_et_bulletins_retirees(): void
    {
        $this->assertFalse(Route::has('eleves.destroy'));
        $this->assertFalse(Route::has('enseignants.destroy'));
        $this->assertFalse(Route::has('parents.destroy'));
        $this->assertFalse(Route::has('finance.bulletins-paie.create'));
        $this->assertFalse(Route::has('finance.bulletins-paie.store'));

        $this->assertTrue(Route::has('eleves.index'));
        $this->assertTrue(Route::has('finance.bulletins-paie.index'));
        $this->assertTrue(Route::has('finance.bulletins-paie.show'));
        $this->assertTrue(Route::has('finance.bulletins-paie.ajustement.destroy'));
    }

    public function test_coordonnees_publiques_issues_de_la_configuration(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $response->assertSee(config('keduc.cabinet.email'));
        $response->assertDontSee('contact@keducbf.com');
        $response->assertDontSee('+226 XX XX XX XX');
    }
}
