<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandlordAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSuperAdmin(array $attrs = []): SuperAdmin
    {
        return SuperAdmin::create(array_merge([
            'nom' => 'Super Admin',
            'email' => 'admin@saascd.test',
            'password' => 'motdepasse',
        ], $attrs));
    }

    public function test_page_de_connexion_est_rendue(): void
    {
        $this->get('http://admin.localhost/admin/login')
            ->assertOk()
            ->assertSee('Espace super-admin')
            ->assertSee('Se connecter');
    }

    public function test_identifiants_valides_connectent_et_redirigent(): void
    {
        $this->makeSuperAdmin();

        $this->post('http://admin.localhost/admin/login', [
            'email' => 'admin@saascd.test',
            'password' => 'motdepasse',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs(SuperAdmin::first(), 'landlord');
    }

    public function test_identifiants_invalides_renvoient_une_erreur(): void
    {
        $this->post('http://admin.localhost/admin/login', [
            'email' => 'inconnu@saascd.test',
            'password' => 'mauvais',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('landlord');
    }

    public function test_le_tableau_de_bord_requiert_l_authentification(): void
    {
        $this->get('http://admin.localhost/admin')
            ->assertRedirect(route('landlord.login'));
    }

    public function test_utilisateur_deja_connecte_ne_revoit_plus_la_connexion(): void
    {
        $sa = $this->makeSuperAdmin();

        $this->actingAs($sa, 'landlord')
            ->get('http://admin.localhost/admin/login')
            ->assertRedirect('/admin');
    }

    public function test_tableau_de_bord_accessible_apres_connexion(): void
    {
        $sa = $this->makeSuperAdmin();

        $this->actingAs($sa, 'landlord')
            ->get('http://admin.localhost/admin')
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee($sa->nom);
    }

    public function test_la_console_landlord_est_invisible_sur_un_domaine_cabinet(): void
    {
        $this->get('http://c1.localhost/admin/login')->assertNotFound();
    }

    public function test_deconnexion_met_fin_a_la_session(): void
    {
        $sa = $this->makeSuperAdmin();

        $this->actingAs($sa, 'landlord')
            ->post('http://admin.localhost/admin/logout')
            ->assertRedirect(route('landlord.login'));

        $this->assertGuest('landlord');
    }
}