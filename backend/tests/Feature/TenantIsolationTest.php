<?php

namespace Tests\Feature;

use App\Models\ParametrePublic;
use App\Models\ParametresPlateforme;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

class TenantIsolationTest extends TenantTestCase
{
    use InteractsWithCabinets;

    public function test_deux_cabinets_isolent_leurs_donnees(): void
    {
        $c1 = $this->makeCabinet('c1');
        $c2 = $this->makeCabinet('c2');

        // Le pipeline de création provisionne un admin par cabinet (T2.4) :
        // chaque base ne contient que SON admin (isolation d'abord).
        tenancy()->initialize($c1);
        $this->assertSame(['admin@c1.local'], User::pluck('email')->all());

        User::create([
            'nom' => 'Alice',
            'prenom' => 'Durand',
            'email' => 'alice@exemple.test',
            'password' => 'secret',
        ]);
        $this->assertSame(['admin@c1.local', 'alice@exemple.test'], User::orderBy('id')->pluck('email')->all());

        tenancy()->initialize($c2);
        $this->assertSame(['admin@c2.local'], User::pluck('email')->all());

        User::create([
            'nom' => 'Bob',
            'prenom' => 'Martin',
            'email' => 'bob@exemple.test',
            'password' => 'secret',
        ]);
        $this->assertSame(['admin@c2.local', 'bob@exemple.test'], User::orderBy('id')->pluck('email')->all());

        // Retour au cabinet c1 : aucune des données de c2 ne fuite.
        tenancy()->initialize($c1);
        $this->assertSame(['admin@c1.local', 'alice@exemple.test'], User::orderBy('id')->pluck('email')->all());

        tenancy()->end();
    }

    public function test_domaine_cabinet_serre_la_page_publique(): void
    {
        // Le pipeline de création seed automatiquement (jobs CreateDatabase,
        // MigrateDatabase, SeedDatabase) : la page publique répond directement.
        $this->makeCabinet('c1');

        // Le web KEduc n'est servi que si l'interrupteur du Landlord est actif
        // (décision B4, T2.10) — on l'active pour tester la livraison.
        ParametresPlateforme::definir(ParametresPlateforme::CLE_ACCES_WEB_KEDUC, true);

        $response = $this->get('http://c1.localhost/');
        $response->assertOk();

        // Les assets statiques sont servis depuis public/ (pas de réécriture
        // stancl vers /tenancy/assets — sinon le site serait rendu « sans CSS »).
        $response->assertDontSee('/tenancy/assets');
        $response->assertSee('/templates/publicpages/assets/css/main.css', false);
        $response->assertSee('/templates/publicpages/assets/js/main.js', false);
    }

    public function test_seeder_tenant_roles_et_parametres_publics(): void
    {
        $c1 = $this->makeCabinet('c1');

        // Rejouer le seed manuellement doit rester idempotent (rôles findOrCreate).
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