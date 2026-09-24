<?php

namespace Tests\Feature;

use Database\Seeders\ClasseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypeCoursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Partie07UiUxSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ClasseSeeder::class);
        $this->seed(TypeCoursSeeder::class);
    }

    public function test_home_contient_canonical_jsonld_et_un_seul_h1(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('application/ld+json', false);

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringNotContainsString('<h1 class="sitename', $html);
    }

    public function test_login_affiche_erreurs_remember_et_pas_de_lien_mort(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('name="remember"', false);
        $response->assertSee('visually-hidden', false);
        $response->assertSee('<link rel="canonical"', false);

        $html = $response->getContent();
        $this->assertStringNotContainsString('href="#"', $html);
        $this->assertStringContainsString('Mot de passe oublié ?', $html);
    }

    public function test_pages_publiques_stables_se_rendent(): void
    {
        $urls = [
            '/',
            '/actualites',
            '/bibliotheque',
            '/librairie',
            '/librairie/categories',
            '/librairie/produits',
            '/temoignages',
            '/demander-un-cours',
            '/librairie/panier',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_panier_est_non_indexable(): void
    {
        $this->get('/librairie/panier')
            ->assertOk()
            ->assertSee('noindex, nofollow', false);
    }

    public function test_robots_txt_et_sitemap_xml_sont_valides(): void
    {
        $this->assertFileExists(public_path('robots.txt'));
        $this->assertFileExists(public_path('sitemap.xml'));

        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Disallow: /admin/', $robots);
        $this->assertStringContainsString('Disallow: /dashboard', $robots);
        $this->assertStringContainsString('Sitemap: ', $robots);

        $sitemap = file_get_contents(public_path('sitemap.xml'));
        $this->assertStringContainsString('<loc>https://', $sitemap);
        $this->assertNotFalse(simplexml_load_string($sitemap));
    }

    public function test_footer_public_sans_lien_mort(): void
    {
        $this->get('/actualites')
            ->assertOk()
            ->assertDontSee('href="#"');
    }
}
