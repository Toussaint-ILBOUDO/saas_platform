<?php

namespace Tests\Feature;

use App\Models\ParametrePublic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\InteractsWithCabinets;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithCabinets;

    /**
     * PostgreSQL interdit CREATE DATABASE dans un bloc de transaction :
     * la base centrale de test est migrée (migrate:fresh) mais non transactée.
     * Les bases tenant sont supprimées en tearDown (ensureCabinetsDropped).
     */
    protected function connectionsToTransact(): array
    {
        return [];
    }

    protected function refreshTestDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
        $this->artisan('migrate:fresh');
        $this->beginDatabaseTransaction();
    }

    protected function tearDown(): void
    {
        $this->ensureCabinetsDropped();

        parent::tearDown();
    }

    public function test_deux_cabinets_isolent_leurs_donnees(): void
    {
        $c1 = $this->makeCabinet('c1');
        $c2 = $this->makeCabinet('c2');

        tenancy()->initialize($c1);

        User::create([
            'nom' => 'Alice',
            'prenom' => 'Durand',
            'email' => 'alice@exemple.test',
            'password' => 'secret',
        ]);
        $this->assertSame(1, User::count());

        tenancy()->initialize($c2);
        $this->assertSame(0, User::count());

        User::create([
            'nom' => 'Bob',
            'prenom' => 'Martin',
            'email' => 'bob@exemple.test',
            'password' => 'secret',
        ]);
        $this->assertSame(1, User::count());

        tenancy()->initialize($c1);
        $this->assertSame('Alice', User::first()->nom);
        $this->assertSame(1, User::count());

        tenancy()->end();
    }

    public function test_domaine_cabinet_serre_la_page_publique(): void
    {
        $this->makeCabinet('c1');

        Artisan::call('tenants:seed', [
            '--tenants' => ['c1'],
            '--class' => 'TenantDatabaseSeeder',
            '--force' => true,
        ]);

        $this->get('http://c1.localhost/')->assertOk();
    }

    public function test_seeder_tenant_roles_et_parametres_publics(): void
    {
        $c1 = $this->makeCabinet('c1');

        Artisan::call('tenants:seed', [
            '--tenants' => ['c1'],
            '--class' => 'TenantDatabaseSeeder',
            '--force' => true,
        ]);

        tenancy()->initialize($c1);

        $roles = Role::pluck('name')->sort()->values()->all();
        $this->assertSame(
            ['admin_cabinet', 'eleve', 'enseignant', 'gestionnaire_librairie', 'parent'],
            $roles
        );

        $this->assertSame(1, ParametrePublic::count());
        $this->assertArrayHasKey('couleurs', ParametrePublic::first()->theme);

        tenancy()->end();
    }

    public function test_domaine_cabinet_inconnu_retourne_404(): void
    {
        $this->get('http://inconnu.localhost/')->assertNotFound();
    }
}