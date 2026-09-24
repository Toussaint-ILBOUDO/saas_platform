<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandlordNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function connecte(): SuperAdmin
    {
        $sa = SuperAdmin::create([
            'nom' => 'Super Admin',
            'email' => 'admin@saascd.test',
            'password' => 'motdepasse',
        ]);

        $this->actingAs($sa, 'landlord');

        return $sa;
    }

    public function test_le_dashboard_affiche_la_navigation_du_landlord(): void
    {
        $this->connecte();

        $html = $this->get('http://admin.localhost/admin')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Tableau de bord', $html);
        $this->assertStringContainsString('Cabinets', $html);
        $this->assertStringContainsString('Facturation', $html);
        $this->assertStringContainsString('Journal', $html);
        $this->assertStringContainsString('Déconnexion', $html);
    }

    public function test_les_sections_placeholders_sont_accessibles(): void
    {
        $this->connecte();

        $this->get('http://admin.localhost/admin/cabinets')->assertOk()->assertSee('Cabinets');
        $this->get('http://admin.localhost/admin/facturation')->assertOk()->assertSee('Facturation');
        $this->get('http://admin.localhost/admin/journal')->assertOk()->assertSee('Journal');
    }

    public function test_une_section_inconnue_retourne_404(): void
    {
        $this->connecte();

        $this->get('http://admin.localhost/admin/inexistant')->assertNotFound();
    }
}