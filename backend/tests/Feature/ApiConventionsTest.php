<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Conventions API (T3.1) : erreurs JSON {message, code, erreurs}, statuts
 * 422/401/404/429, messages en français.
 */
class ApiConventionsTest extends TenantTestCase
{
    use InteractsWithCabinets;

    public function test_validation_retourne_shape_json(): void
    {
        $this->makeCabinet('c1');

        $this->postJson('http://c1.localhost/api/public/demandes-cours', [])
            ->assertStatus(422)
            ->assertJson([
                'code' => 'VALIDATION',
                'erreurs' => [
                    'nom_parent' => ['Le nom du parent est obligatoire.'],
                ],
            ]);
    }

    public function test_non_connecte_retourne_401(): void
    {
        $this->makeCabinet('c1');

        $this->getJson('http://c1.localhost/api/auth/moi')
            ->assertStatus(401)
            ->assertJson(['code' => 'NON_CONNECTE']);
    }

    public function test_route_inconnue_retourne_404_json(): void
    {
        $this->makeCabinet('c1');

        $this->getJson('http://c1.localhost/api/inexistant')
            ->assertStatus(404)
            ->assertJson(['code' => 'INTROUVABLE']);
    }

    public function test_cabinet_inconnu_retourne_404_cabinet(): void
    {
        $this->getJson('http://inconnu.localhost/api/public/cabinet')
            ->assertStatus(404)
            ->assertJson(['code' => 'CABINET_INCONNU']);
    }

    public function test_connexion_throttlee_429(): void
    {
        $this->makeCabinet('c1');
        Cache::flush();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('http://c1.localhost/api/auth/connexion', [
                'email' => 'inconnu@exemple.test',
                'password' => 'mauvais',
            ])->assertStatus(422);
        }

        $this->postJson('http://c1.localhost/api/auth/connexion', [
            'email' => 'inconnu@exemple.test',
            'password' => 'mauvais',
        ])
            ->assertStatus(429)
            ->assertJson(['code' => 'TROP_DE_REQUETES']);
    }

    public function test_page_web_du_cabinet_reste_404_sans_interrupteur(): void
    {
        // Régression T2.10 : l'API fonctionne sans l'interrupteur web KEduc,
        // mais le web lui reste fermé.
        $this->makeCabinet('c1');

        $this->get('http://c1.localhost/')->assertNotFound();
        $this->getJson('http://c1.localhost/api/public/cabinet')->assertOk();
    }

    public function test_documentation_openapi_disponible(): void
    {
        // T3.7 : la doc Scramble est servie sur le domaine central (hors prod)
        // et expose bien la convention OpenAPI 3.1 + les routes API.
        $response = $this->getJson('/docs/api.json');
        $response->assertOk();
        $paths = $response->json('paths');
        $this->assertSame('3.1.0', $response->json('openapi'));
        $this->assertSame('SAAS-Cabinet — API des cabinets', $response->json('info.title'));
        $this->assertStringEndsWith('/api', $response->json('servers.0.url'));
        $this->assertArrayHasKey('post', $paths['/auth/connexion'] ?? []);
        // 38 = routes existantes (auth, notifications, rôles, contenu public,
        // FAQ sections + questions, API publique : cabinet, actualités, faq,
        // documents, produits, stats, enseignants, témoignages, références,
        // demandes, commandes).
        $this->assertCount(38, $paths);
        $this->assertTrue(
            collect(array_keys($paths))->contains(fn (string $cle) => str_contains($cle, '/public/references')),
            'La route /api/public/references doit figurer dans la doc OpenAPI.'
        );
    }
}