<?php

namespace Tests\Feature;

use App\Models\Cabinet;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Impersonation Landlord → admin cabinet (T2.6) : émission du jeton,
 * connexion côté cabinet, bannière/session, sortie journalisée.
 */
class ImpersonationTest extends TenantTestCase
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

    private function emettreJeton(string $slug): string
    {
        $this->connecte();
        $response = $this->post("http://admin.localhost/admin/cabinets/{$slug}/impersoner");
        $target = $response->headers->get('Location');
        $this->assertNotNull($target);
        $this->assertStringStartsWith("http://{$slug}.localhost/impersonation/", $target);

        return $target;
    }

    public function test_connexion_en_tant_que_admin_du_cabinet(): void
    {
        $c1 = $this->makeCabinet('c1');
        $adminId = $c1->fresh()->admin_utilisateur_id;
        $this->assertNotNull($adminId, 'Le pipeline doit renseigner admin_utilisateur_id.');

        $target = $this->emettreJeton('c1');

        $this->assertDatabaseHas('journal_plateforme', ['action' => 'impersonation.emise']);

        $this->get($target)->assertRedirect('http://c1.localhost/');

        $this->assertAuthenticated('web');
        $this->assertSame((string) $adminId, (string) auth('web')->id());
        $this->assertSame('c1', session('impersonation.cabinet_id'));

        $this->assertDatabaseHas('journal_plateforme', ['action' => 'impersonation.debut'], 'pgsql');

        // Jeton à usage unique
        $this->assertDatabaseCount('tenant_user_impersonation_tokens', 0, 'pgsql');
    }

    public function test_jeton_usage_unique_rejet_la_seconde_fois(): void
    {
        $this->makeCabinet('c1');
        $target = $this->emettreJeton('c1');

        $this->get($target)->assertRedirect('http://c1.localhost/');
        $this->get($target)->assertNotFound();
    }

    public function test_sortie_met_fin_a_l_impersonation_et_journalise(): void
    {
        $this->makeCabinet('c1');
        $target = $this->emettreJeton('c1');
        $this->get($target)->assertRedirect('http://c1.localhost/');
        $this->assertAuthenticated('web');

        $this->post('http://c1.localhost/impersonation/sortir')
            ->assertRedirect('http://c1.localhost');

        $this->assertGuest('web');
        $this->assertNull(session('impersonation'));
        $this->assertDatabaseHas('journal_plateforme', ['action' => 'impersonation.fin'], 'pgsql');
    }

    public function test_jeton_inconnu_rejete(): void
    {
        $this->makeCabinet('c1');
        $this->get('http://c1.localhost/impersonation/inconnu')->assertNotFound();
    }

    public function test_jeton_expire_rejete(): void
    {
        $this->makeCabinet('c1');
        DB::table('tenant_user_impersonation_tokens')->insert([
            'token' => str_repeat('a', 128),
            'tenant_id' => 'c1',
            'user_id' => '1',
            'auth_guard' => 'web',
            'redirect_url' => 'http://c1.localhost/',
            'created_at' => now()->subMinutes(20),
        ]);

        $this->get('http://c1.localhost/impersonation/' . str_repeat('a', 128))->assertStatus(419);
    }

    public function test_jeton_d_un_autre_cabinet_rejete(): void
    {
        $this->makeCabinet('c1');
        $this->makeCabinet('c2');
        DB::table('tenant_user_impersonation_tokens')->insert([
            'token' => str_repeat('b', 128),
            'tenant_id' => 'c2',
            'user_id' => '1',
            'auth_guard' => 'web',
            'redirect_url' => 'http://c2.localhost/',
            'created_at' => now(),
        ]);

        // Consommé sur le mauvais domaine
        $this->get('http://c1.localhost/impersonation/' . str_repeat('b', 128))->assertForbidden();

        // Le jeton reste intact
        $this->assertDatabaseCount('tenant_user_impersonation_tokens', 1, 'pgsql');
    }

    public function test_impersonation_refusee_si_cabinet_pas_actif(): void
    {
        $c1 = $this->makeCabinet('c1');
        $this->connecte();

        $this->post("http://admin.localhost/admin/cabinets/{$c1->id}/statut", ['statut' => 'suspendu']);

        $this->post("http://admin.localhost/admin/cabinets/{$c1->id}/impersoner")
            ->assertStatus(409);

        $this->assertDatabaseCount('tenant_user_impersonation_tokens', 0, 'pgsql');
    }

    public function test_impersonation_refusee_sans_admin_provisionne(): void
    {
        // Scénario de migration : cabinet historique sans admin renseigné.
        $this->makeCabinet('c1');
        DB::connection('pgsql')->table('tenants')->where('id', 'c1')->update(['admin_utilisateur_id' => null]);
        $this->assertNull(Cabinet::find('c1')->admin_utilisateur_id);

        $this->connecte();
        $this->post("http://admin.localhost/admin/cabinets/c1/impersoner")
            ->assertStatus(422);

        $this->assertDatabaseCount('tenant_user_impersonation_tokens', 0, 'pgsql');
    }

    public function test_la_banniere_est_wiree_dans_le_layout_panel(): void
    {
        $this->assertTrue(view()->exists('panel.partials.impersonation-banniere'));

        $layout = file_get_contents(resource_path('views/panel/layouts/app.blade.php'));
        $this->assertStringContainsString('panel.partials.impersonation-banniere', $layout);
    }
}