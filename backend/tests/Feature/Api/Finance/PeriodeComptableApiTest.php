<?php

namespace Tests\Feature\Api\Finance;

use App\Models\PeriodeComptable;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API Finance — périodes comptables (T7A.1).
 *
 * Les routes sont murées par « auth:web » + « role:admin_cabinet » et servies
 * par domaine (`http://<slug>.localhost/api/...`) : c'est ce qui permet à
 * stancl de résoudre la base du cabinet.
 */
class PeriodeComptableApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    /**
     * Crée le cabinet, applique un mot de passe connu à son admin et ouvre
     * une session web via l'API de connexion.
     */
    private function connecteAdmin(string $slug): void
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);
        User::where('email', "admin@{$slug}.local")
            ->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => "admin@{$slug}.local",
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function api(string $slug, string $chemin): string
    {
        return "http://{$slug}.localhost/api/finance/periodes{$chemin}";
    }

    public function test_api_exige_connexion_et_role_admin_cabinet(): void
    {
        $this->makeCabinet('c1');

        // Non connecté → 401.
        $this->getJson($this->api('c1', ''))
            ->assertStatus(401)
            ->assertJson(['code' => 'NON_CONNECTE']);

        // Connecté sans rôle admin_cabinet → 403 (l'enseignant ne gère pas
        // les périodes comptables).
        tenancy()->initialize('c1');
        $enseignant = User::create([
            'nom' => 'Enseignant',
            'prenom' => 'Paul',
            'email' => 'paul@c1.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $enseignant->assignRole('enseignant');
        tenancy()->end();

        $this->postJson('http://c1.localhost/api/auth/connexion', [
            'email' => 'paul@c1.local',
            'password' => 'Secret1234',
        ])->assertOk();

        $this->getJson($this->api('c1', ''))->assertStatus(403);
    }

    public function test_index_expose_le_contrat_attendu(): void
    {
        $this->connecteAdmin('c2');

        $this->postJson($this->api('c2', ''), [
            'label' => 'Mars 2026',
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-31',
            'type' => 'mensuel',
        ])->assertCreated();

        $response = $this->getJson($this->api('c2', ''));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'label',
                    'date_debut',
                    'date_fin',
                    'type',
                    'statut',
                    'est_ouverte',
                    'est_cloturee',
                    'cloturee_par',
                    'cloturee_at',
                ],
            ],
        ]);
    }

    public function test_store_cree_une_periode_ouverte(): void
    {
        $this->connecteAdmin('c3');

        $response = $this->postJson($this->api('c3', ''), [
            'label' => 'Avril 2026',
            'date_debut' => '2026-04-01',
            'date_fin' => '2026-04-30',
            'type' => 'mensuel',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.label', 'Avril 2026');
        $response->assertJsonPath('data.date_debut', '2026-04-01');
        $response->assertJsonPath('data.date_fin', '2026-04-30');
        // Une période naît ouverte : le statut n'est pas saisissable (D-051).
        $response->assertJsonPath('data.statut', PeriodeComptable::OUVERTE);
        $response->assertJsonPath('data.est_ouverte', true);
        $response->assertJsonPath('data.est_cloturee', false);
    }

    public function test_store_refuse_un_chevauchement(): void
    {
        $this->connecteAdmin('c4');

        $this->postJson($this->api('c4', ''), [
            'label' => 'Juin 2026',
            'date_debut' => '2026-06-01',
            'date_fin' => '2026-06-30',
            'type' => 'mensuel',
        ])->assertCreated();

        $chevauchement = $this->postJson($this->api('c4', ''), [
            'label' => 'Juin (chevauchement)',
            'date_debut' => '2026-06-15',
            'date_fin' => '2026-06-20',
            'type' => 'mensuel',
        ]);

        $chevauchement->assertStatus(422)->assertJson([
            'code' => 'VALIDATION',
            'erreurs' => [
                'date_debut' => ['Ces dates chevauchent la période « Juin 2026 » (01/06/2026 → 30/06/2026).'],
            ],
        ]);
    }

    public function test_store_valide_les_bornes_et_le_type(): void
    {
        $this->connecteAdmin('c5');

        // date_fin antérieure à date_debut.
        $this->postJson($this->api('c5', ''), [
            'label' => 'Période inversée',
            'date_debut' => '2026-07-31',
            'date_fin' => '2026-07-01',
            'type' => 'mensuel',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION');

        // Type inconnu.
        $this->postJson($this->api('c5', ''), [
            'label' => 'Type invalide',
            'date_debut' => '2026-07-01',
            'date_fin' => '2026-07-31',
            'type' => 'decennal',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION');
    }

    public function test_close_puis_reopen_tracent_les_deux_actions(): void
    {
        $this->connecteAdmin('c6');

        $id = $this->postJson($this->api('c6', ''), [
            'label' => 'Mai 2026',
            'date_debut' => '2026-05-01',
            'date_fin' => '2026-05-31',
            'type' => 'mensuel',
        ])->json('data.id');

        $close = $this->patchJson($this->api('c6', "/{$id}/close"));

        $close->assertOk();
        $close->assertJsonPath('data.statut', PeriodeComptable::CLOTUREE);
        $close->assertJsonPath('data.est_cloturee', true);
        $this->assertNotNull($close->json('data.cloturee_at'));
        $this->assertNotNull($close->json('data.cloturee_par'));

        $reopen = $this->patchJson($this->api('c6', "/{$id}/reopen"));

        $reopen->assertOk();
        $reopen->assertJsonPath('data.statut', PeriodeComptable::OUVERTE);
        $reopen->assertJsonPath('data.est_ouverte', true);
        $this->assertNull($reopen->json('data.cloturee_at'));
    }

    public function test_show_renvoie_la_periode(): void
    {
        $this->connecteAdmin('c7');

        $id = $this->postJson($this->api('c7', ''), [
            'label' => 'Septembre 2026',
            'date_debut' => '2026-09-01',
            'date_fin' => '2026-09-30',
            'type' => 'mensuel',
        ])->json('data.id');

        $this->getJson($this->api('c7', "/{$id}"))
            ->assertOk()
            ->assertJsonPath('data.label', 'Septembre 2026');

        $this->getJson($this->api('c7', '/999999'))->assertNotFound();
    }

    public function test_update_modifie_libelle_et_bornes(): void
    {
        $this->connecteAdmin('c8');

        $id = $this->postJson($this->api('c8', ''), [
            'label' => 'Octobre 2026',
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-31',
            'type' => 'mensuel',
        ])->json('data.id');

        $this->putJson($this->api('c8', "/{$id}"), [
            'label' => 'Octobre 2026 (rectificatif)',
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-31',
            'type' => 'mensuel',
        ])
            ->assertOk()
            ->assertJsonPath('data.label', 'Octobre 2026 (rectificatif)');
    }
}