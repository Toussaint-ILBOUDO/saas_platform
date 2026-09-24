<?php

namespace Tests\Feature;

use App\Models\Cabinet;
use App\Models\ParametresCabinet;
use App\Models\SuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * CRUD cabinets depuis la console Landlord (T2.3).
 */
class LandlordCabinetsTest extends TenantTestCase
{
    use InteractsWithCabinets;

    protected function connecte(): void
    {
        $this->actingAs(
            SuperAdmin::create([
                'nom' => 'Super Admin',
                'email' => 'admin@saascd.test',
                'password' => 'motdepasse',
            ]),
            'landlord'
        );
    }

    /**
     * Insertion directe d'un cabinet SANS pipeline (évite CREATE DATABASE) :
     * réservée aux tests qui ne testent pas le provisionnement.
     */
    private function insererCabinet(string $id, string $nom = 'Cabinet test'): Cabinet
    {
        DB::table('tenants')->insert([
            'id' => $id,
            'nom' => $nom,
            'sous_domaine' => $id,
            'status' => 'actif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('domains')->insert([
            'domain' => "{$id}.localhost",
            'tenant_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ParametresCabinet::updateOrCreate(
            ['cabinet_id' => $id],
            ['tarif_abonnement' => 5000, 'tarif_par_eleve' => 500, 'fonctionnalites_activees' => ['pedagogie']]
        );

        return Cabinet::findOrFail($id);
    }

    public function test_la_liste_affiche_les_cabinets(): void
    {
        $this->connecte();
        $this->insererCabinet('c1', 'Cabinet Alpha');
        $this->insererCabinet('c2', 'Cabinet Beta');

        $this->get('http://admin.localhost/admin/cabinets')
            ->assertOk()
            ->assertSee('Cabinets')
            ->assertSee('Cabinet Alpha')
            ->assertSee('Cabinet Beta')
            ->assertSee('c1.localhost');
    }

    public function test_creation_provisionne_le_cabinet_complet(): void
    {
        $this->connecte();

        $this->post('http://admin.localhost/admin/cabinets', [
            'id' => 'cabinet-omega',
            'nom' => 'Cabinet Omega',
            'sous_domaine' => 'omega',
            'email' => 'omega@example.com',
            'telephone' => '+22670000000',
        ])->assertRedirect(route('landlord.cabinets.show', 'cabinet-omega'));

        $this->createdCabinets[] = Cabinet::findOrFail('cabinet-omega');

        $this->assertDatabaseHas('tenants', ['id' => 'cabinet-omega', 'nom' => 'Cabinet Omega', 'status' => 'actif']);
        $this->assertDatabaseHas('domains', ['domain' => 'omega.localhost', 'tenant_id' => 'cabinet-omega']);
        $this->assertDatabaseHas('parametres_cabinet', ['cabinet_id' => 'cabinet-omega']);

        $this->assertVraiQueLaBaseTenantEstProvisionnee('keduc_test_cabinet-omega');
    }

    /**
     * La base du cabinet doit exister et avoir été seedée (rôles spatie, D-027).
     */
    private function assertVraiQueLaBaseTenantEstProvisionnee(string $base): void
    {
        $config = array_merge(config('database.connections.pgsql'), ['database' => $base]);
        config(['database.connections.tenant_provision_check' => $config]);

        $roles = DB::connection('tenant_provision_check')->table('roles')->count();

        $this->assertGreaterThan(0, $roles, "La base tenant [{$base}] n'a pas été provisionnée (rôles absents).");

        DB::purge('tenant_provision_check');
    }

    public function test_un_slug_invalide_est_refuse(): void
    {
        $this->connecte();

        $this->post('http://admin.localhost/admin/cabinets', [
            'id' => 'Cabinet Invalid!',
            'nom' => 'Cabinet X',
        ])->assertSessionHasErrors('id');

        $this->assertDatabaseMissing('tenants', ['id' => 'Cabinet Invalid!']);
    }

    public function test_un_slug_deja_utilise_est_refuse(): void
    {
        $this->connecte();
        $this->insererCabinet('c1');

        $this->post('http://admin.localhost/admin/cabinets', [
            'id' => 'c1',
            'nom' => 'Doublon',
        ])->assertSessionHasErrors('id');
    }

    public function test_les_infos_du_cabinet_sont_modifiables(): void
    {
        $this->connecte();
        $cabinet = $this->insererCabinet('c1', 'Avant');

        $this->put("http://admin.localhost/admin/cabinets/{$cabinet->id}", [
            'nom' => 'Après',
            'sous_domaine' => 'c1',
            'status' => 'suspendu',
            'email' => 'après@example.com',
            'telephone' => '',
        ])->assertRedirect(route('landlord.cabinets.show', $cabinet));

        $this->assertDatabaseHas('tenants', ['id' => 'c1', 'nom' => 'Après', 'status' => 'suspendu']);
    }

    public function test_les_tarifs_et_fonctionnalites_sont_modifiables(): void
    {
        $this->connecte();
        $cabinet = $this->insererCabinet('c1');

        $this->put("http://admin.localhost/admin/cabinets/{$cabinet->id}/parametres", [
            'tarif_abonnement' => 25000,
            'tarif_par_eleve' => 2000,
            'fonctionnalites_activees' => ['pedagogie', 'bibliotheque', 'finance'],
        ])->assertRedirect(route('landlord.cabinets.show', $cabinet));

        $param = ParametresCabinet::where('cabinet_id', 'c1')->firstOrFail();

        $this->assertSame(25000.0, $param->tarif_abonnement);
        $this->assertSame(2000.0, $param->tarif_par_eleve);
        $this->assertEqualsCanonicalizing(['pedagogie', 'bibliotheque', 'finance'], $param->fonctionnalites_activees);
    }

    public function test_un_module_inconnu_est_refuse(): void
    {
        $this->connecte();
        $cabinet = $this->insererCabinet('c1');

        $this->put("http://admin.localhost/admin/cabinets/{$cabinet->id}/parametres", [
            'tarif_abonnement' => 5000,
            'tarif_par_eleve' => 0,
            'fonctionnalites_activees' => ['module-inconnu'],
        ])->assertSessionHasErrors('fonctionnalites_activees.0');
    }

    public function test_l_acces_aux_cabinets_requiert_l_authentification(): void
    {
        $this->get('http://admin.localhost/admin/cabinets')
            ->assertRedirect(route('landlord.login'));
    }
}