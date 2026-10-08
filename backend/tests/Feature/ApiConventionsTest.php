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
        // 73 = auth, notifications, rôles, contenu public + logo, FAQ
        // sections + questions, API publique (cabinet, logo, actualités, faq,
        // documents, produits, stats, enseignants, témoignages, références,
        // demandes, commandes) + T7A.1 finance (6 paths périodes) + T7A.2
        // référentiels (10 paths : 8 CRUD classes/matières/types/enseignants
        // + les 2 bascules d'état matière) + T7A.3 contrats & affectations
        // (7 paths) + « mes cours » (1 path) + T7A.4 planning (2 paths
        // enseignant) + « mon planning » (1 path) + T7A.5 cahier de texte
        // (6 paths : CRUD enseignant + affectations + PDF séance, et
        // historique parent/élève + son PDF) + le sélecteur « mes enfants »
        // (1 path) + T7A.7 rapport mensuel (5 paths enseignant : index,
        // dépôt, détail/suppression, corriger, re-soumettre, PDF — et
        // 4 paths admin : liste, détail, valider, rejeter = 9 paths) et
        // T7A.8 factures (3 paths parent : liste, détail, PDF ; 5 paths
        // admin : liste (GET, sans l'aperçu du même chemin que la
        // génération), aperçu, génération, détail, PDF, règlement — soit
        // 5 clés de chemin car `/admin/factures` porte à la fois GET et POST
        // = 8 paths) et T7A.9 bulletins de paie (7 paths enseignant : liste,
        // détail, PDF, consulter, valider, contester, confirmer-reception ;
        // 9 paths admin : liste + génération sur un même chemin, aperçu,
        // types d'ajustement, détail, PDF, correction, paiement, ajout et
        // suppression d'ajustement) = 16 paths + l'administration des demandes
        // de cours (7 paths : liste, détail, valider, refuser et les trois
        // étapes de constitution du dossier parent/élève/contrat) = 116 paths,
        // soit 114 + « mes contrats » (2 paths parent : liste et fiche).
        $this->assertCount(116, $paths);
        $this->assertTrue(
            collect(array_keys($paths))->contains(fn (string $cle) => str_contains($cle, '/public/references')),
            'La route /api/public/references doit figurer dans la doc OpenAPI.'
        );

        // Les endpoints livrés en T7A.1 à T7A.4 doivent être documentés.
        foreach ([
            '/finance/periodes',
            '/finance/periodes/{periode}/close',
            '/finance/periodes/{periode}/reopen',
            '/pedagogie/classes',
            '/pedagogie/matieres',
            '/pedagogie/matieres/{matiere}/activer',
            '/pedagogie/matieres/{matiere}/desactiver',
            '/pedagogie/type-cours',
            '/pedagogie/enseignants',
            '/pedagogie/contrats',
            '/pedagogie/contrats/{contrat}/statut',
            '/pedagogie/contrats/{contrat}/affectations',
            '/pedagogie/contrats/{contrat}/affectations/{affectation}/statut',
            '/mes-cours',
            // T7A.3 — portail parent : pendant « famille » de /pedagogie/contrats.
            '/mes-contrats',
            '/mes-contrats/{contrat}',
            '/pedagogie/planning',
            '/pedagogie/planning/{planningCours}',
            '/mes-planning',
            // T7A.5 — le cahier de texte expose deux périmètres distincts :
            // l'enseignant écrit, la famille ne fait que lire son enfant.
            '/enseignant/cahiers-textes',
            '/enseignant/cahiers-textes/affectations',
            '/enseignant/cahiers-textes/{cahier}',
            '/enseignant/cahiers-textes/{cahier}/pdf',
            '/mes-enfants',
            '/mes-enfants/{eleve}/cahiers-textes',
            '/mes-enfants/{eleve}/cahiers-textes/historique-pdf',
            // T7A.7 — le rapport mensuel expose deux périmètres distincts :
            // l'enseignant dépose/corrige/re-soumet/supprime ; l'admin liste
            // tout le cabinet et valide/rejette (D-051).
            '/pedagogie/rapports-mensuels',
            '/pedagogie/rapports-mensuels/periodes',
            '/pedagogie/rapports-mensuels/{rapport}',
            '/pedagogie/rapports-mensuels/{rapport}/corriger',
            '/pedagogie/rapports-mensuels/{rapport}/resoumettre',
            '/pedagogie/rapports-mensuels/{rapport}/pdf',
            '/admin/pedagogie/rapports-mensuels',
            '/admin/pedagogie/rapports-mensuels/{rapport}/valider',
            '/admin/pedagogie/rapports-mensuels/{rapport}/rejeter',
            // T7A.8 — la facture expose deux périmètres : le parent ne lit que
            // ses factures (liste, détail, PDF), l'admin gère tout (liste,
            // aperçu sans écriture, génération, détail, PDF, règlement).
            '/mes-factures',
            '/mes-factures/{facture}',
            '/mes-factures/{facture}/pdf',
            '/admin/factures',
            '/admin/factures/preview',
            '/admin/factures/{facture}',
            '/admin/factures/{facture}/pdf',
            '/admin/factures/{facture}/paiement',
            // T7A.9 — le bulletin de paie expose deux périmètres : l'enseignant
            // déroule son cycle (consulter, valider, contester, confirmer la
            // réception), l'admin aperçoit/génère, corrige et verse.
            '/mes-bulletins',
            '/mes-bulletins/{bulletin}',
            '/mes-bulletins/{bulletin}/pdf',
            '/mes-bulletins/{bulletin}/consulter',
            '/mes-bulletins/{bulletin}/valider',
            '/mes-bulletins/{bulletin}/contester',
            '/mes-bulletins/{bulletin}/confirmer-reception',
            '/admin/bulletins-paie',
            '/admin/bulletins-paie/preview',
            '/admin/bulletins-paie/types-ajustement',
            '/admin/bulletins-paie/{bulletin}',
            '/admin/bulletins-paie/{bulletin}/pdf',
            '/admin/bulletins-paie/{bulletin}/corriger',
            '/admin/bulletins-paie/{bulletin}/paiement',
            '/admin/bulletins-paie/{bulletin}/ajustements',
            '/admin/bulletins-paie/{bulletin}/ajustements/{ajustement}',
            // Demandes de cours — l'admin du cabinet liste, consulte et traite.
            '/pedagogie/demandes-cours',
            '/pedagogie/demandes-cours/{demandeCours}',
            '/pedagogie/demandes-cours/{demandeCours}/valider',
        ] as $attendu) {
            $this->assertArrayHasKey(
                $attendu,
                $paths,
                "La route {$attendu} doit figurer dans la doc OpenAPI."
            );
        }
    }
}