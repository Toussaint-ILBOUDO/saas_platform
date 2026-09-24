<?php

namespace Tests\Feature;

use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;
use App\Models\TypeCours;
use App\Models\User;
use App\Modules\Pedagogie\Services\RapportMensuelPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * Partie 02 — Sécurité des PDF de rapports mensuels :
 * routes protégées, Policy propriétaire/admin, stockage privé.
 */
class RapportMensuelPdfSecurityTest extends TestCase
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

    /**
     * Construit le graphe minimal (enseignant, élève, parent, contrat,
     * période) nécessaire à un rapport mensuel.
     */
    private function createRapport(): array
    {
        $enseignantUser = $this->createUserWithRole('enseignant');
        $profil = EnseignantProfil::create(['user_id' => $enseignantUser->id]);

        $parentUser = $this->createUserWithRole('parent');
        $eleveUser = $this->createUserWithRole('eleve');
        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $parentUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $typeCours = TypeCours::create(['libelle' => 'A domicile']);
        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $typeCours->id,
            'date_debut' => now()->subMonth()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
        ]);

        $periode = PeriodeComptable::create([
            'label' => '2026-07',
            'date_debut' => now()->startOfMonth()->toDateString(),
            'date_fin' => now()->endOfMonth()->toDateString(),
        ]);

        $rapport = RapportMensuelEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $profil->id,
            'periode_id' => $periode->id,
            'volume_horaire_cumule' => 12,
            'statut' => 'soumis',
        ]);

        return [
            'rapport' => $rapport,
            'enseignantUser' => $enseignantUser,
            'parentUser' => $parentUser,
            'adminUser' => $this->createUserWithRole('admin'),
        ];
    }

    private function fakePdf()
    {
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('setOptions')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('FAKE-PDF-CONTENT');
        $pdf->shouldReceive('stream')->andReturn(
            response('FAKE-PDF', 200, ['Content-Type' => 'application/pdf'])
        );
        $pdf->shouldReceive('download')->andReturn(
            response('FAKE-PDF', 200, ['Content-Type' => 'application/pdf'])
        );

        return $pdf;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCÈS AUX ROUTES PDF
    |--------------------------------------------------------------------------
    */

    public function test_invite_ne_peut_pas_acceder_au_pdf_d_un_rapport(): void
    {
        ['rapport' => $rapport] = $this->createRapport();

        $this->get("/rapports-mensuels/{$rapport->id}/pdf")
            ->assertRedirect(route('login'));

        $this->get("/rapports-mensuels/{$rapport->id}/pdf/download")
            ->assertRedirect(route('login'));
    }

    public function test_un_utilisateur_non_autorise_ne_peut_pas_telecharger_le_pdf_d_autrui(): void
    {
        ['rapport' => $rapport] = $this->createRapport();
        $parent = $this->createUserWithRole('parent');

        $this->actingAs($parent)
            ->get("/rapports-mensuels/{$rapport->id}/pdf")
            ->assertForbidden();

        $this->actingAs($parent)
            ->get("/rapports-mensuels/{$rapport->id}/pdf/download")
            ->assertForbidden();
    }

    public function test_le_proprietaire_peut_telecharger_le_pdf_de_son_rapport(): void
    {
        ['rapport' => $rapport, 'enseignantUser' => $enseignant] = $this->createRapport();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($this->fakePdf());

        $this->actingAs($enseignant)
            ->get("/rapports-mensuels/{$rapport->id}/pdf/download")
            ->assertOk();
    }

    public function test_admin_peut_acceder_au_pdf_de_n_importe_quel_rapport(): void
    {
        ['rapport' => $rapport, 'adminUser' => $admin] = $this->createRapport();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($this->fakePdf());

        $this->actingAs($admin)
            ->get("/rapports-mensuels/{$rapport->id}/pdf")
            ->assertOk();
    }

    public function test_policy_autorise_proprietaire_admin_et_refuse_les_autres(): void
    {
        ['rapport' => $rapport, 'enseignantUser' => $enseignant, 'adminUser' => $admin] = $this->createRapport();
        $autreEnseignant = $this->createUserWithRole('enseignant');

        $this->actingAs($enseignant)->assertTrue(Gate::allows('view', $rapport));
        $this->actingAs($admin)->assertTrue(Gate::allows('view', $rapport));
        $this->actingAs($autreEnseignant)->assertFalse(Gate::allows('view', $rapport));
    }

    /*
    |--------------------------------------------------------------------------
    | STOCKAGE PRIVÉ DES PDF SAUVEGARDÉS
    |--------------------------------------------------------------------------
    */

    public function test_le_pdf_sauvegarde_est_stocke_sur_un_disque_prive(): void
    {
        Storage::fake('local');

        ['rapport' => $rapport] = $this->createRapport();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($this->fakePdf());

        $path = app(RapportMensuelPdfService::class)->save($rapport);

        $this->assertStringStartsWith('rapports-mensuels/rapport-mensuel-', $path);

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }
}
