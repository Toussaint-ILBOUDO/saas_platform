<?php

namespace Tests\Feature\Api\Librairie;

use App\Models\Cabinet;
use App\Models\CategorieProduit;
use App\Models\Commande;
use App\Models\ParametreLibrairie;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Librairie commerciale — commandes, suivi public, délégation.
 *
 * Verrouille :
 *  - commande sans compte : prix/stock recalculés en base, référence
 *    `SIGLE-AAAAMMJJ-XXXXXXX`, idempotence (double clic = 1 commande) ;
 *  - suivi public par code : aucune donnée interne, annulation client ;
 *  - « mes-commandes » : 403 sur la référence d'un autre client ;
 *  - délégation : le libraire peut retirer à l'admin l'accès au dashboard,
 *    au CA et aux commandes — restriction serveur, le libraire garde
 *    toujours l'accès, l'admin ne peut pas se réautoriser.
 */
class LibrairieApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function connecteMembre(string $slug, string $email, string $role): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        if (! Cabinet::find($slug)) {
            $this->makeCabinet($slug);
        }

        tenancy()->initialize($slug);
        $user = User::where('email', $email)->first() ?? User::create([
            'nom' => 'Membre',
            'prenom' => 'Librairie',
            'email' => $email,
            'password' => Hash::make('Secret1234'),
        ]);
        $user->assignRole($role);
        $user->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function apiPublic(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/public{$chemin}";
    }

    private function adminApi(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/admin/librairie{$chemin}";
    }

    private function withTenant(string $slug, callable $callback): mixed
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        tenancy()->initialize($slug);
        $resultat = $callback();
        tenancy()->end();

        return $resultat;
    }

    /** Crée le cabinet (si besoin), une catégorie et un produit actif. */
    private function creerProduit(string $slug, string $nom, float $prix, ?int $stock = null, ?float $prixPromo = null): int
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        if (! Cabinet::find($slug)) {
            $this->makeCabinet($slug);
        }

        return $this->withTenant($slug, function () use ($nom, $prix, $stock, $prixPromo) {
            $categorie = CategorieProduit::firstOrCreate(
                ['nom' => 'Manuels scolaires'],
                ['ordre' => 1, 'is_active' => true]
            );

            $produit = Produit::create([
                'categorie_id' => $categorie->id,
                'nom' => $nom,
                'prix' => $prix,
                'prix_promo' => $prixPromo,
                'stock' => $stock,
                'is_active' => true,
                'est_archive' => false,
            ]);

            return $produit->id;
        });
    }

    private function commandeValide(string $slug, int $produitId, array $surcharge = []): array
    {
        return array_merge([
            'nom_client' => 'Diallo',
            'prenom_client' => 'Aminata',
            'telephone_client' => '+225 07 00 00 00 00',
            'adresse_livraison' => null,
            'is_livraison' => false,
            'panier' => [['produit_id' => $produitId, 'quantite' => 2]],
            'cle_idempotence' => 'test-'.uniqid(),
        ], $surcharge);
    }

    public function test_commande_prix_recalcules_cote_serveur(): void
    {
        $slug = 'lib-prix';
        $produitId = $this->creerProduit($slug, 'Manuel de maths 6e', 1500.0);

        // Le navigateur tente d'envoyer un prix falsifié (champ non accepté).
        $reponse = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId, [
                'montant_total' => 1,
                'frais_livraison' => 0,
            ]));

        $reponse->assertStatus(201);
        $this->assertSame(3000.0, (float) $reponse->json('commande.montant_total'));
        $this->assertStringStartsWith(
            ParametreLibrairie::sigle().'-'.now()->format('Ymd').'-',
            $reponse->json('commande.reference')
        );
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{7}$/', substr($reponse->json('commande.reference'), -7));
    }

    public function test_frais_livraison_proviennent_des_reglages_structure(): void
    {
        $slug = 'lib-frais';
        $produitId = $this->creerProduit($slug, 'Cahier de textes', 500.0);

        $this->withTenant($slug, fn () => ParametreLibrairie::ecrire(
            ParametreLibrairie::CLE_FRAIS_LIVRAISON,
            '1000'
        ));

        // Retrait : aucun frais.
        $retrait = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId));
        $retrait->assertStatus(201);
        $this->assertEquals(0, $retrait->json('commande.frais_livraison'));

        // Livraison : frais serveur (la valeur client est ignorée).
        $reponse = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId, [
                'is_livraison' => true,
                'adresse_livraison' => 'Cocody, rue des Jardins',
                'frais_livraison' => 99,
                'cle_idempotence' => 'livraison-'.uniqid(),
            ]));

        $reponse->assertStatus(201);
        $this->assertEquals(1000, $reponse->json('commande.frais_livraison'));
        // montant_total = articles uniquement ; les frais sont séparés.
        $this->assertEquals(1000, $reponse->json('commande.montant_total'));
    }

    public function test_livraison_sans_adresse_rejetee(): void
    {
        $slug = 'lib-adresse';
        $produitId = $this->creerProduit($slug, 'Règle graduée', 200.0);

        $reponse = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId, [
                'is_livraison' => true,
            ]));

        $reponse->assertStatus(422);
        $this->assertNotEmpty(
            $reponse->json('erreurs.adresse_livraison'),
            'L\'adresse est exigée pour une livraison. Erreurs : '.json_encode($reponse->json('erreurs'))
        );
    }

    public function test_idempotence_double_clic_une_seule_commande(): void
    {
        $slug = 'lib-idempo';
        $produitId = $this->creerProduit($slug, 'Carte géographique', 800.0, 10);
        $cle = 'doublon-'.uniqid();

        $premiere = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId, [
                'cle_idempotence' => $cle,
            ]));
        $premiere->assertStatus(201);

        // Même clé, même panier : la commande existante est renvoyée.
        $seconde = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId, [
                'cle_idempotence' => $cle,
            ]));
        $seconde->assertStatus(201);

        $this->assertSame(
            $premiere->json('commande.reference'),
            $seconde->json('commande.reference'),
            'La même clé d\'idempotence doit renvoyer la commande existante.'
        );

        $this->withTenant($slug, function () use ($slug) {
            $this->assertSame(1, Commande::count(), 'Aucune commande en double.');
        });
    }

    public function test_stock_insuffisant_rejette_la_commande(): void
    {
        $slug = 'lib-stock';
        $produitId = $this->creerProduit($slug, 'Manuscript rare', 2500.0, 1);

        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId, [
                'panier' => [['produit_id' => $produitId, 'quantite' => 5]],
            ]))
            ->assertStatus(422);

        // Stock inchangé après l'échec.
        $stock = $this->withTenant($slug, fn () => Produit::find($produitId)->stock);
        $this->assertSame(1, $stock);
    }

    public function test_stock_decremente_apres_commande(): void
    {
        $slug = 'lib-decrement';
        $produitId = $this->creerProduit($slug, 'Trousse complète', 1200.0, 5);

        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId, [
                'panier' => [['produit_id' => $produitId, 'quantite' => 3]],
            ]))
            ->assertStatus(201);

        $stock = $this->withTenant($slug, fn () => Produit::find($produitId)->stock);
        $this->assertSame(2, $stock);
    }

    public function test_devis_serveur_recalcule_et_signale_indisponibilite(): void
    {
        $slug = 'lib-devis';
        $produitId = $this->creerProduit($slug, 'Guide du parent', 700.0, 1);

        $reponse = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/panier/devis'), [
                'lignes' => [
                    ['produit_id' => $produitId, 'quantite' => 10],
                ],
            ]);

        $reponse->assertOk()
            ->assertJsonPath('data.disponible', false);
        $this->assertEquals(700, $reponse->json('data.lignes.0.prix_unitaire'));
        $this->assertEquals(7000, $reponse->json('data.lignes.0.sous_total'));
    }

    public function test_promotion_prise_en_compte(): void
    {
        $slug = 'lib-promo';
        $produitId = $this->creerProduit($slug, 'Sac de sport', prix: 5000.0, stock: 3, prixPromo: 3500.0);

        $reponse = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId, [
                'panier' => [['produit_id' => $produitId, 'quantite' => 1]],
            ]));

        $reponse->assertStatus(201);
        $this->assertEquals(3500, $reponse->json('commande.montant_total'));
    }

    public function test_suivi_public_parcours_et_annulation(): void
    {
        $slug = 'lib-suivi';
        $produitId = $this->creerProduit($slug, 'Cahier de brouillon', 300.0, 10);

        $reference = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId))
            ->assertStatus(201)
            ->json('commande.reference');

        // Suivi public sans compte : données client minimales.
        $suivi = $this->getJson($this->apiPublic($slug, "/commandes/{$reference}"));
        $suivi->assertOk()
            ->assertJsonPath('data.reference', $reference)
            ->assertJsonPath('data.statut', 'en_attente')
            ->assertJsonPath('data.annulable', true);
        $this->assertNotEmpty($suivi->json('data.etapes'));
        $this->assertStringNotContainsString('note_interne', json_encode($suivi->json()));

        // Annulation immédiate tant que la commande est en attente.
        $this->postJson($this->apiPublic($slug, "/commandes/{$reference}/annuler"), [
            'motif' => 'Commande passée par erreur.',
        ])->assertOk()
            ->assertJsonPath('statut', 'annulee')
            ->assertJsonPath('annulation_demandee', false);

        // Plus annulable : refus.
        $this->postJson($this->apiPublic($slug, "/commandes/{$reference}/annuler"))
            ->assertStatus(422);
    }

    public function test_annulation_confirmee_seulement_demande_tracée(): void
    {
        $slug = 'lib-annul-conf';
        $produitId = $this->creerProduit($slug, 'Dictionnaire', 2200.0, 5);
        $reference = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId))
            ->json('commande.reference');

        // Le personnel confirme.
        $this->connecteMembre($slug, "libraire@{$slug}.local", 'gestionnaire_librairie');
        $this->patchJson($this->adminApi($slug, "/commandes/{$reference}/statut"), [
            'statut' => Commande::STATUT_CONFIRMEE,
        ])->assertOk();

        // Le client demande l'annulation : demande tracée, statut inchangé.
        $this->flushSession();
        $this->postJson($this->apiPublic($slug, "/commandes/{$reference}/annuler"), [
            'motif' => 'Plus besoin.',
        ])->assertOk()
            ->assertJsonPath('statut', 'confirmee')
            ->assertJsonPath('annulation_demandee', true);

        // Le libraire valide l'annulation.
        $this->connecteMembre($slug, "libraire@{$slug}.local", 'gestionnaire_librairie');
        $this->patchJson($this->adminApi($slug, "/commandes/{$reference}/statut"), [
            'statut' => Commande::STATUT_ANNULEE,
            'motif' => 'Accord client.',
        ])->assertOk()
            ->assertJsonPath('statut', 'annulee');
    }

    public function test_mes_commandes_protege_les_references_d_autrui(): void
    {
        $slug = 'lib-mes';
        $produitId = $this->creerProduit($slug, 'Agenda scolaire', 900.0, 4);

        $reference = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId))
            ->json('commande.reference');

        // Un autre client connecté ne voit ni cette commande ni son détail.
        $this->connecteMembre($slug, "autre@{$slug}.local", 'parent');

        $this->getJson("http://{$slug}.localhost/api/mes-commandes/{$reference}")
            ->assertStatus(403);
        $this->getJson("http://{$slug}.localhost/api/mes-commandes/{$reference}/pdf")
            ->assertStatus(403);

        $liste = $this->getJson("http://{$slug}.localhost/api/mes-commandes")->assertOk();
        $this->assertSame(0, $liste->json('meta.total'));
    }

    public function test_mes_commandes_detail_et_annulation_pour_le_proprietaire(): void
    {
        $slug = 'lib-mes2';
        $produitId = $this->creerProduit($slug, 'Livre de lecture', 1500.0, 4);

        // Le client passe sa commande connecté : la commande lui appartient.
        $this->connecteMembre($slug, "client@{$slug}.local", 'parent');
        $reference = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId))
            ->json('commande.reference');

        $this->getJson("http://{$slug}.localhost/api/mes-commandes/{$reference}")
            ->assertOk()
            ->assertJsonPath('data.reference', $reference)
            ->assertJsonPath('data.annulable', true);

        $this->postJson("http://{$slug}.localhost/api/mes-commandes/{$reference}/annuler")
            ->assertOk()
            ->assertJsonPath('statut', 'annulee');
    }

    public function test_pdf_recapitulatif_publique(): void
    {
        $slug = 'lib-pdf';
        $produitId = $this->creerProduit($slug, 'Cahier de liaison', 400.0, 3);

        $reference = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId))
            ->json('commande.reference');

        $this->get($this->apiPublic($slug, "/commandes/{$reference}/recapitulatif"))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_dashboard_et_stats_pour_le_libraire(): void
    {
        $slug = 'lib-dashboard';
        $produitId = $this->creerProduit($slug, 'Calculatrice', 3000.0, 8);

        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId))
            ->assertStatus(201);

        $this->connecteMembre($slug, "libraire@{$slug}.local", 'gestionnaire_librairie');

        $this->getJson($this->adminApi($slug, '/dashboard'))
            ->assertOk()
            ->assertJsonPath('data.produits', 1)
            ->assertJsonPath('data.commandes', 1)
            ->assertJsonPath('data.ca_accessible', true)
            ->assertJsonPath('data.commandes_par_statut.0.statut', 'en_attente');
        $this->assertEquals(6000, $this->getJson($this->adminApi($slug, '/dashboard'))->json('data.chiffre_affaires'));

        $this->getJson($this->adminApi($slug, '/produits'))
            ->assertOk()
            ->assertJsonPath('data.0.nom', 'Calculatrice');

        $this->getJson($this->adminApi($slug, '/commandes'))
            ->assertOk()
            ->assertJsonPath('data.0.reference', $this->withTenant($slug, fn () => Commande::first()->reference));
    }

    public function test_crud_produit_admin_et_archivage(): void
    {
        $slug = 'lib-crud';

        $this->connecteMembre($slug, "admin@{$slug}.local", 'admin_cabinet');
        $categorieId = $this->withTenant($slug, function () {
            return CategorieProduit::firstOrCreate(
                ['nom' => 'Fournitures'],
                ['ordre' => 2, 'is_active' => true]
            )->id;
        });

        $creation = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->adminApi($slug, '/produits'), [
                'categorie_id' => $categorieId,
                'nom' => 'Stylo bleu',
                'prix' => 150,
                'stock' => 20,
                'is_active' => true,
            ]);
        $creation->assertStatus(201);
        $produitId = $creation->json('produit.id');

        $this->withHeaders(['Accept' => 'application/json'])
            ->putJson($this->adminApi($slug, "/produits/{$produitId}"), [
                'prix' => 200,
                'prix_promo' => 175,
            ])
            ->assertOk();

        $this->patchJson($this->adminApi($slug, "/produits/{$produitId}/basculer"))
            ->assertOk()
            ->assertJsonPath('is_active', false);

        // Produit commandé → archivage, pas de suppression d'historique.
        $this->withTenant($slug, function () use ($produitId) {
            $produit = Produit::find($produitId);
            $produit->update(['is_active' => true]);
            $categorie = $produit->categorie_id;
            $commande = Commande::create([
                'reference' => Commande::genererReference(),
                'nom_client' => 'Client',
                'telephone_client' => '0102030405',
                'is_livraison' => false,
                'montant_total' => 175,
                'frais_livraison' => 0,
                'statut' => Commande::STATUT_LIVREE,
            ]);
            $commande->lignes()->create([
                'produit_id' => $produitId,
                'quantite' => 1,
                'prix_unitaire' => 175,
                'sous_total' => 175,
            ]);

            return $categorie;
        });

        $this->deleteJson($this->adminApi($slug, "/produits/{$produitId}"))
            ->assertOk();

        $this->withTenant($slug, function () use ($produitId) {
            $produit = Produit::withTrashed()->find($produitId);
            $this->assertNotNull($produit);
            $this->assertTrue($produit->trashed() || $produit->est_archive);
            $this->assertFalse((bool) $produit->is_active);
        });
    }

    public function test_delegation_retrait_acces_admin(): void
    {
        $slug = 'lib-deleg';

        $this->connecteMembre($slug, "admin@{$slug}.local", 'admin_cabinet');

        // Par défaut : l'admin accède au dashboard et au CA.
        $this->getJson($this->adminApi($slug, '/dashboard'))
            ->assertOk()
            ->assertJsonPath('data.ca_accessible', true);

        // L'admin ne peut PAS modifier les clés de délégation (réservé libraire).
        $this->withHeaders(['Accept' => 'application/json'])
            ->patchJson($this->adminApi($slug, '/parametres'), [
                'admin_peut_voir_dashboard' => true,
                'admin_peut_voir_ca' => true,
            ])
            ->assertStatus(403);

        // Le libraire retire l'accès de l'admin.
        $this->flushSession();
        $this->connecteMembre($slug, "libraire@{$slug}.local", 'gestionnaire_librairie');

        $this->withHeaders(['Accept' => 'application/json'])
            ->patchJson($this->adminApi($slug, '/parametres'), [
                'admin_peut_voir_dashboard' => false,
                'admin_peut_voir_ca' => false,
                'admin_peut_voir_commandes' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.admin_peut_voir_dashboard', false);

        // Le libraire conserve l'accès.
        $this->getJson($this->adminApi($slug, '/dashboard'))
            ->assertOk();

        // L'admin est désormais bloqué côté serveur, même par URL directe.
        $this->flushSession();
        $this->connecteMembre($slug, "admin@{$slug}.local", 'admin_cabinet');

        $this->getJson($this->adminApi($slug, '/dashboard'))
            ->assertStatus(403);
        $this->getJson($this->adminApi($slug, '/produits'))
            ->assertStatus(403);
        $this->getJson($this->adminApi($slug, '/commandes'))
            ->assertStatus(403);
        // Le CA est masqué même quand le dashboard est accessible :
        // ici dashboard ET CA sont retirés.
        $this->getJson($this->adminApi($slug, '/parametres'))
            ->assertOk()
            ->assertJsonPath('data.admin_peut_voir_ca', false);

        // L'admin ne peut pas se réautoriser.
        $this->withHeaders(['Accept' => 'application/json'])
            ->patchJson($this->adminApi($slug, '/parametres'), [
                'admin_peut_voir_dashboard' => true,
            ])
            ->assertStatus(403);

        // Un enseignant n'a jamais accès à la console.
        $this->flushSession();
        $this->connecteMembre($slug, "prof@{$slug}.local", 'enseignant');
        $this->getJson($this->adminApi($slug, '/dashboard'))->assertStatus(403);
        $this->getJson($this->adminApi($slug, '/parametres'))->assertStatus(403);
    }

    public function test_commande_sans_compte_suivi_puis_visibility_admin(): void
    {
        $slug = 'lib-sanscompte';
        $produitId = $this->creerProduit($slug, 'Pochette plastique', 100.0, 20);

        $reference = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId))
            ->assertStatus(201)
            ->json('commande.reference');

        $this->connecteMembre($slug, "libraire@{$slug}.local", 'gestionnaire_librairie');

        $detail = $this->getJson($this->adminApi($slug, "/commandes/{$reference}"));
        $detail->assertOk()
            ->assertJsonPath('data.reference', $reference)
            ->assertJsonPath('data.nom_client', 'Diallo');
    }

    public function test_transition_statut_invalide_rejetee(): void
    {
        $slug = 'lib-transition';
        $produitId = $this->creerProduit($slug, 'Rame de papier', 350.0, 10);
        $reference = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson($this->apiPublic($slug, '/commandes'), $this->commandeValide($slug, $produitId))
            ->json('commande.reference');

        $this->connecteMembre($slug, "libraire@{$slug}.local", 'gestionnaire_librairie');

        // en_attente → livrée : transition interdite.
        $this->withHeaders(['Accept' => 'application/json'])
            ->patchJson($this->adminApi($slug, "/commandes/{$reference}/statut"), [
                'statut' => Commande::STATUT_LIVREE,
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'TRANSITION_INVALIDE');
    }
}
