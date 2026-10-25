<?php

namespace Tests\Feature\Landlord;

use App\Models\LigneTypePlateforme;
use App\Models\ParametresPlateforme;
use App\Models\ServicePlateforme;
use App\Models\SuperAdmin;
use App\Models\User;
use Database\Seeders\CataloguePlateformeSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;
use App\Modules\Plateforme\Services\ContratCabinetService;
use App\Modules\Plateforme\Services\FacturationCabinetService;

/**
 * P8 — Catalogue éditable de la PLATEFORME (console super admin) :
 *
 * - seed : paramètre `plateforme` = « Magis Plus Center », 10 services du
 *   contrat décrits, lignes types actives (idempotent) ;
 * - écran « /admin/catalogue » : affichage + édition immédiate des textes ;
 * - lignes types : création, mise à jour, désactivation (pas de suppression
 *   dure) ;
 * - la préparation de facture propose les lignes types actives comme
 *   briques cliquables.
 */
class CataloguePlateformeTest extends TenantTestCase
{
    use InteractsWithCabinets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePlateformeSeeder::class);

        $this->actingAs(
            SuperAdmin::create([
                'nom' => 'Catalogue Admin',
                'email' => 'catalogue@saascd.test',
                'password' => 'motdepasse',
            ]),
            'landlord',
        );
    }

    public function test_le_seeder_installe_identite_services_et_lignes_types(): void
    {
        $identite = ParametresPlateforme::obtenir('plateforme');
        $this->assertSame('Magis Plus Center', $identite['nom']);
        $this->assertNotEmpty($identite['tagline']);

        $services = ServicePlateforme::query()->get();
        $this->assertCount(10, $services);
        $this->assertSame(10, $services->whereNotNull('description')->count());

        $lignes = LigneTypePlateforme::query()->get();
        $this->assertGreaterThanOrEqual(6, $lignes->count());
        $this->assertGreaterThan(0, $lignes->where('sens', 'credit')->count());

        // Idempotence : relancer le seeder ne double rien.
        $this->seed(CataloguePlateformeSeeder::class);
        $this->assertSame(10, ServicePlateforme::query()->count());
        $this->assertSame(1, ServicePlateforme::where('cle', 'frais_creation_contrat')->count());
    }

    public function test_ecran_catalogue_affiche_sections_et_gere_l_edition(): void
    {
        $reponse = $this->get(route('landlord.catalogue.index'));
        $reponse->assertOk()
            ->assertSee('Catalogue de la plateforme')
            ->assertSee('Abonnements mensuels')
            ->assertSee('Frais de création du contrat')
            ->assertSee('Google Ads — campagne de promotion locale');

        // Édition : la nouvelle description passe à l'écran de suite.
        $service = ServicePlateforme::where('cle', 'frais_contrat_actif')->firstOrFail();
        $this->from(route('landlord.catalogue.index'))
            ->put(route('landlord.catalogue.services.update', $service), [
                'libelle' => $service->libelle,
                'description' => 'Facturés à CHAQUE activation d\'un contrat de cours.',
                'categorie' => $service->categorie,
                'actif' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(
            'Facturés à CHAQUE activation d\'un contrat de cours.',
            $service->refresh()->description,
        );
        $this->get(route('landlord.catalogue.index'))->assertSee('Facturés à CHAQUE activation d\'un contrat de cours.');
    }

    public function test_lignes_types_cree_modifie_desactive(): void
    {
        $this->post(route('landlord.catalogue.lignes-types.creer'), [
            'libelle' => 'Séminaire pédagogique',
            'sens' => 'debit',
            'montant' => 75000,
            'description' => 'Formation d\'équipe sur une journée.',
        ])->assertRedirect()->assertSessionHas('success');

        $ligne = LigneTypePlateforme::where('libelle', 'Séminaire pédagogique')->firstOrFail();
        $this->assertTrue($ligne->actif);
        $this->assertSame(75000, $ligne->montant);

        $this->put(route('landlord.catalogue.lignes-types.update', $ligne), [
            'libelle' => 'Séminaire pédagogique (journée)',
            'sens' => 'debit',
            'montant' => 80000,
            'description' => 'Tarif 2026.',
            'actif' => 1,
        ])->assertRedirect();

        $this->assertSame(80000, $ligne->refresh()->montant);

        // Désactivation (pas de suppression dure) : disparaît des briques
        // mais reste en base pour l'historique des factures passées.
        $this->delete(route('landlord.catalogue.lignes-types.desactiver', $ligne))->assertRedirect();
        $this->assertFalse($ligne->refresh()->actif);
        $this->assertDatabaseHas('lignes_types_plateforme', ['id' => $ligne->id]);
    }

    public function test_briques_dans_la_preparation_de_facture(): void
    {
        $cabinet = $this->makeCabinet('catalogue-prep');

        $reponse = $this->get(route('landlord.facturation.preparer', [
            'cabinet' => $cabinet->id,
            'debut' => '2026-10-01',
            'fin' => '2026-10-31',
        ]));

        $reponse->assertOk()
            ->assertSee('Catalogue :')
            ->assertSee('Google Ads — campagne de promotion locale')
            ->assertSee('Remise commerciale');
    }

    public function test_ecran_contrat_sert_la_grille_avec_explications_catalogue(): void
    {
        $cabinet = $this->makeCabinet('catalogue-contrat');

        // Écran liste (aucun contrat) : 200.
        $this->get(route('landlord.contrats.index', $cabinet))->assertOk();

        // Brouillon créé puis fiche : la grille groupe par catégories et
        // affiche les libellés (catalogue seedé) + leur explication.
        $this->post(route('landlord.contrats.store', $cabinet))->assertRedirect();

        $contrat = \App\Models\ContratCabinet::where('cabinet_id', $cabinet->id)->firstOrFail();

        $this->get(route('landlord.contrats.show', [$cabinet, $contrat]))
            ->assertOk()
            ->assertSee('Frais de création du contrat')
            ->assertSee("compte, paramétrage initial");

        // PUT brouillon : tarif + exonération par CODE colonne (grille).
        $this->put(route('landlord.contrats.update', [$cabinet, $contrat]), [
            'responsable_nom' => 'Direction test',
            'date_effet' => '2026-10-01',
            'tarifs-frais_creation_contrat' => '15000',
            'tarifs-abonnement_mensuel_eleve' => '2000',
            'tarifs-taux_commission_librairie' => '5,5',
            'exonerations' => ['abonnement_mensuel_enseignant'],
        ])->assertRedirect();

        $contrat->refresh();
        $this->assertSame(15000, $contrat->frais_creation_contrat);
        $this->assertSame(['abonnement_mensuel_enseignant'], $contrat->exonerations);
    }

    public function test_console_facture_cycle_avec_briques_catalogue_et_api(): void
    {
        $cabinet = $this->makeCabinet('facture-console');
        app(ContratCabinetService::class)->ajouterVersion($cabinet->id, [
            'date_effet' => '2026-01-01',
            'tarifs' => ['frais_creation_contrat' => 10000],
            'exonerations' => [],
        ], null);

        $service = app(FacturationCabinetService::class);
        $facture = $service->creerFactureBrouillon(
            $cabinet->id,
            new Carbon('2026-10-01'),
            new Carbon('2026-10-31'),
            $service->contratValide($cabinet->id),
            [],
        );

        // Fiche console : sections titrées + briques catalogue + totaux.
        $this->get(route('landlord.facturation.show', $facture))
            ->assertOk()
            ->assertSee('Activités facturables automatiques')
            ->assertSee('Lignes ajoutées à la main')
            ->assertSee('Réductions (crédits)')
            ->assertSee('Google Ads — campagne de promotion locale')
            ->assertSee('Compléter la facture')
            ->assertSee('Paiements encaissés');

        // Ajout d'une ligne manuelle via le catalogue depuis la console.
        $this->post(route('landlord.facturation.lignes.ajouter', $facture), [
            'libelle' => 'Google Ads — campagne de promotion locale',
            'sens' => 'debit',
            'quantite' => 1,
            'montant' => 25000,
            'motif' => 'Publicité payée en direct par le super-admin au profit du cabinet (justificatif demandé).',
        ])->assertRedirect();

        $facture->refresh();
        $this->assertSame(25000, $facture->montant_total);

        // Émission par la console : numérotée, statut émise, écran à jour.
        $this->post(route('landlord.facturation.emettre', $facture))->assertRedirect();
        $facture->refresh();
        $this->assertSame('emise', $facture->statut);
        $this->assertMatchesRegularExpression('/^FC-2026\d{2}-\d{5}$/', $facture->numero_facture);

        $this->get(route('landlord.facturation.show', $facture))
            ->assertOk()
            ->assertSee('Paiements encaissés')
            ->assertSee($facture->numero_facture);

        // Paiement complet prérempli par le formulaire (reste à payer = tout).
        $this->post(route('landlord.facturation.paiements.ajouter', $facture), [
            'montant' => 25000,
            'mode' => 'orange',
            'date_paiement' => '2026-10-20',
        ])->assertRedirect();
        $facture->refresh();
        $this->assertSame('payee', $facture->statut);

        // API « détail » du cabinet : lignes exposées proprement — l'admin
        // cabinet renseigné par le pipeline de création (makeCabinet).
        tenancy()->initialize($cabinet->id);
        try {
            User::where('email', "admin@{$cabinet->id}.local")->update(['password' => Hash::make('Secret1234')]);
        } finally {
            tenancy()->end();
        }

        $this->postJson("http://{$cabinet->id}.localhost/api/auth/connexion", [
            'email' => "admin@{$cabinet->id}.local",
            'password' => 'Secret1234',
        ])->assertOk();

        $reponse = $this->getJson("http://{$cabinet->id}.localhost/api/admin/mes-factures-plateforme/{$facture->id}");
        $reponse->assertOk()
            ->assertJsonPath('data.numero', $facture->numero_facture)
            ->assertJsonPath('data.lignes.0.libelle', 'Google Ads — campagne de promotion locale')
            ->assertJsonPath('data.lignes.0.source', 'manuelle')
            ->assertJsonPath('data.montant_total', 25000)
            ->assertJsonCount(1, 'data.lignes');
    }
}
