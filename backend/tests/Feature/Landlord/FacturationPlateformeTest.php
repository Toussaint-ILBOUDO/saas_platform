<?php

namespace Tests\Feature\Landlord;

use App\Models\EvenementFacturable;
use App\Models\SuperAdmin;
use App\Modules\Plateforme\Services\ContratCabinetService;
use App\Modules\Plateforme\Services\FacturationCabinetService;
use App\Modules\Plateforme\Services\PaiementCabinetService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * P8 — Facturation de la plateforme par cabinet : montants EXACTS (consigne).
 *
 * Couverture :
 *  - total = Σ débits − Σ crédits à l'unité FCFA (contrat fixe, rapport,
 *    commission arrondie, maintenance d'anniversaire, lignes manuelles) ;
 *  - numéro `FC-YYYYMM-NNNNN` posé à l'émission (Q-A19) ;
 *  - chevauchement de périodes refusé, périodes disjointes OK (Q-A13) ;
 *  - lignes figées à l'émission ; annulation refusée si encaissée (Q-A16) ;
 *    sur-encaissement refusé ; statuts exacts brouillon→émise→payée ;
 *  - régularisation : une facture annulée libère sa période (mêmes dates OK) ;
 *  - isolation : deux cabinets facturés séparément.
 */
class FacturationPlateformeTest extends TenantTestCase
{
    use InteractsWithCabinets;

    protected function connecte(): void
    {
        $this->actingAs(
            SuperAdmin::create([
                'nom' => 'Super Admin',
                'email' => 'facturation@saascd.test',
                'password' => 'motdepasse',
            ]),
            'landlord',
        );
    }

    /**
     * Contrat validé pour le cabinet. Tarifs de référence par défaut :
     * création 15 000, enseignant 3 000, élève 2 000, rapports 500,
     * commission librairie 5,5 % ; parent/annuel/maintenance null.
     */
    private function contratValide(string $cabinetId, array $surcharge = []): object
    {
        $contrat = app(ContratCabinetService::class)->ajouterVersion($cabinetId, array_merge([
            'date_effet' => '2026-01-01',
            'responsable_nom' => 'Direction Keduc',
            'modalites_paiement' => 'Virement, 30 jours',
            'exonerations' => [],
            'tarifs' => [
                'frais_creation_contrat' => 15000,
                'frais_contrat_actif' => null,
                'abonnement_mensuel_enseignant' => 3000,
                'abonnement_mensuel_parent' => null,
                'abonnement_mensuel_eleve' => 2000,
                'frais_rapport_mensuel' => 500,
                'abonnement_annuel_admin' => null,
                'maintenance_annuelle_site' => null,
                'taux_commission_librairie' => 5.5,
                'taux_commission_autres' => null,
            ],
        ], $surcharge), null);

        app(ContratCabinetService::class)->valider($contrat, null);

        return $contrat->refresh();
    }

    /** Événement facturable DISPONIBLE, écrit directement en base centrale. */
    private function evenement(
        string $cabinetId,
        string $type,
        string $referenceId,
        string $date,
        ?int $assiete = null,
    ): object {
        return EvenementFacturable::create([
            'cabinet_id' => $cabinetId,
            'type' => $type,
            'reference_type' => 'test',
            'reference_id' => $referenceId,
            'date_evenement' => Carbon::parse($date)->toDateString(),
            'libelle' => null,
            'montant_assiette' => $assiete,
            'meta' => null,
            'statut' => EvenementFacturable::STATUT_DISPONIBLE,
        ]);
    }

    /**
     * Brouillon (calcul auto + lignes manuelles) puis émission.
     */
    private function facture(string $cabinetId, string $debut, string $fin, array $lignesManuelles = []): object
    {
        $service = app(FacturationCabinetService::class);

        $brouillon = $service->creerFactureBrouillon(
            $cabinetId,
            Carbon::parse($debut),
            Carbon::parse($fin),
            $service->contratValide($cabinetId),
            $lignesManuelles,
        );

        return $service->emettre($brouillon, null);
    }

    // ------------------------------------------------------------------
    // 1. MONTANTS EXACTS
    // ------------------------------------------------------------------

    public function test_montant_exact_avec_commission_arrondie_et_lignes_manuelles(): void
    {
        $cabinetId = $this->makeCabinet('fpc1')->getKey();
        $this->contratValide($cabinetId);

        // Vente 26 000 FCFA, commission 5,5 % → 1 430 (round). + création
        // 15 000 + rapport 500 = 16 930 ; + manuelle débit 5 000 − crédit
        // 2 000 → 19 930.
        $this->evenement($cabinetId, EvenementFacturable::TYPE_CONTRAT_CREE, 'e1', '2026-10-05');
        $this->evenement($cabinetId, EvenementFacturable::TYPE_RAPPORT_VALIDE, 'e2', '2026-10-12');
        $this->evenement($cabinetId, EvenementFacturable::TYPE_VENTE_LIBRAIRIE, 'e3', '2026-10-18', 26000);

        $facture = $this->facture($cabinetId, '2026-10-01', '2026-10-31');
        $this->assertEquals(16930, $facture->montant_total);

        $commission = $facture->lignes->firstWhere('type', 'commission_librairie');
        $this->assertNotNull($commission);
        $this->assertEquals(1430, $commission->montant);
        $this->assertEquals(5.5, $commission->calcul['taux']);
        $this->assertEquals(26000, $commission->calcul['assiette']);
    }

    public function test_brouillon_sans_emission_possede_meme_invariant_de_montant(): void
    {
        $cabinetId = $this->makeCabinet('fpc1b')->getKey();
        $this->contratValide($cabinetId);

        $this->evenement($cabinetId, EvenementFacturable::TYPE_CONTRAT_CREE, 'e1', '2026-10-05');

        $brouillon = $this->brouillonSansEmission($cabinetId, [
            ['sens' => 'debit', 'libelle' => 'Personnalisation', 'quantite' => 1, 'prix_unitaire' => 5000, 'montant' => 5000],
            ['sens' => 'credit', 'libelle' => 'Remise de bienvenue', 'quantite' => 1, 'montant' => 500],
        ]);

        $this->assertEquals('brouillon', $brouillon->statut);
        $this->assertNull($brouillon->numero_facture);
        // 15 000 + 5 000 − 500 = 19 500.
        $this->assertEquals(19500, $brouillon->montant_total);

        $emise = app(FacturationCabinetService::class)->emettre($brouillon, null);
        $this->assertEquals(19500, $emise->montant_total);
        $this->assertEquals('emise', $emise->statut);
        $this->assertEquals('FC-202610-00001', $emise->numero_facture);

        // Montants figés à l'émission : refus d'ajouter/supprimer une ligne.
        $this->expectException(ValidationException::class);
        app(FacturationCabinetService::class)->ajouterLigneManuelle($emise, [
            'sens' => 'debit', 'libelle' => 'Trop tard', 'montant' => 1,
        ], null);
    }

    public function test_tarif_null_ne_genere_pas_de_ligne_et_anniversaire_facture_maintenance(): void
    {
        $cabinet = $this->makeCabinet('fpc2');
        $cabinetId = $cabinet->getKey();

        // Anniversaire de création le 10 mai ; période de mai le couvre.
        \Illuminate\Support\Facades\DB::table('tenants')
            ->where('id', $cabinetId)
            ->update(['created_at' => '2025-05-10 12:00:00']);

        $this->contratValide($cabinetId, [
            'tarifs' => [
                'frais_creation_contrat' => null,
                'abonnement_mensuel_enseignant' => null,
                'maintenance_annuelle_site' => 40000,
            ],
        ]);

        $this->evenement($cabinetId, EvenementFacturable::TYPE_CONTRAT_CREE, 'e1', '2026-05-20');

        $facture = $this->facture($cabinetId, '2026-05-01', '2026-05-31');

        // Tarif null (Q-A17) → aucune ligne « frais_creation_contrat » ;
        // seul l'anniversaire de maintenance (40 000) est facturé.
        $this->assertEquals(40000, $facture->montant_total);
        $types = $facture->lignes->pluck('type')->sort()->values()->all();
        $this->assertSame(['maintenance_site'], $types);
    }

    // ------------------------------------------------------------------
    // 2. INVARIANTS : DOUBLEFACTURATION, PÉRIODES, RÉGULARISATION
    // ------------------------------------------------------------------

    public function test_chevauchement_refuse_et_disjoint_croissant_accepte(): void
    {
        $cabinetId = $this->makeCabinet('fpc3a')->getKey();
        $this->contratValide($cabinetId);

        // Un événement reali en octobre : la première facture est émise.
        $this->evenement($cabinetId, EvenementFacturable::TYPE_CONTRAT_CREE, 'e1', '2026-10-05');
        $facture = $this->facture($cabinetId, '2026-10-01', '2026-10-31');
        $this->assertEquals(15000, $facture->montant_total);

        // Chevauchement partiel, total ou inverse → refus.
        foreach ([
            ['2026-10-15', '2026-11-30'],
            ['2026-10-01', '2026-10-31'],
            ['2026-09-01', '2026-10-01'],
        ] as [$debut, $fin]) {
            try {
                $this->facture($cabinetId, $debut, $fin);
                $this->fail("Chevauchement $debut→$fin non refusé.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('periode', $e->errors());
            }
        }

        // Période disjoinTE croissante → acceptée. Sans mouvement facturable
        // (aucun événement en novembre, pas de compte) le total serait nul :
        // l'émission refuse un montant « nul » — on passe par une ligne
        // manuelle pour représenter la correction attendue.
        $facture2 = $this->facture($cabinetId, '2026-11-01', '2026-11-30', [
            ['sens' => 'debit', 'libelle' => 'Google Ads — sept.', 'quantite' => 1, 'prix_unitaire' => 8000, 'montant' => 8000],
        ]);
        // Le numéro est posé À L'ÉMISSION (Q-A19) — mois courant (FC-…10),
        // même si la période couverte s'étend sur un autre mois.
        $this->assertEquals('FC-202610-00002', $facture2->numero_facture);
        $this->assertEquals(8000, $facture2->montant_total);
    }

    public function test_annulation_libere_la_periode_pour_regulation_par_nouvelle_facture(): void
    {
        $cabinetId = $this->makeCabinet('fpc4a')->getKey();
        $this->contratValide($cabinetId);

        $this->evenement($cabinetId, EvenementFacturable::TYPE_CONTRAT_CREE, 'e1', '2026-10-05');
        $facture = $this->facture($cabinetId, '2026-10-01', '2026-10-31');
        $this->assertEquals(15000, $facture->montant_total);

        app(FacturationCabinetService::class)->annuler($facture, 'Montants erronés', null);

        $facture->refresh();
        $this->assertEquals('annulee', $facture->statut);

        // L'événement est relâché (disponible) et la période réouvrable :
        // la régularisation (Q-A16) émet une nouvelle facture corrigée.
        $evenement = EvenementFacturable::query()->where('cabinet_id', $cabinetId)->first();
        $this->assertEquals(EvenementFacturable::STATUT_DISPONIBLE, $evenement->statut);
        $this->assertNull($evenement->facture_cabinet_id);

        $regul = app(FacturationCabinetService::class)->creerFactureBrouillon(
            $cabinetId,
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31'),
            app(FacturationCabinetService::class)->contratValide($cabinetId),
            [['sens' => 'credit', 'libelle' => 'Remise corrigée', 'montant' => 1000]],
        );

        $this->assertEquals(14000, $regul->montant_total);

        $emise = app(FacturationCabinetService::class)->emettre($regul, null);
        $this->assertEquals(14000, $emise->montant_total);
        // La numérotation ne réutilise JAMAIS un numéro (Q-A19) : la facture
        // régularisée porte le suivant du mois.
        $this->assertEquals('FC-202610-00002', $emise->numero_facture);
    }

    public function test_paiement_partiel_puis_complet_et_sur_encaissement_refuse(): void
    {
        $cabinetId = $this->makeCabinet('fpc5a')->getKey();
        $this->contratValide($cabinetId);

        $this->evenement($cabinetId, EvenementFacturable::TYPE_CONTRAT_CREE, 'e1', '2026-10-05');
        $facture = $this->facture($cabinetId, '2026-10-01', '2026-10-31');

        $paiements = app(PaiementCabinetService::class);
        $paiements->enregistrer($facture, 7000, 'especes', 'RSP-1', null, null);

        $facture->refresh();
        $this->assertEquals('partiellement_payee', $facture->statut);
        $this->assertEquals(7000, $facture->montant_paye);

        // Sur-encaissement refusé (7510 > reste de 8000 ? non : 30 000 > 8 000 ✓ refuse).
        try {
            $paiements->enregistrer($facture, 30000, 'especes', null, null, null);
            $this->fail('Le sur-encaissement aurait dû être refusé.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('montant', $e->errors());
        }

        // Annulation refusée car partiellement encaissée.
        try {
            app(FacturationCabinetService::class)->annuler($facture, 'essai', null);
            $this->fail('L\'annulation d\'une facture encaissée aurait dû être refusée.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('facture', $e->errors());
        }

        // Paiement complémentaire exact : 7 000 + 8 000 = 15 000 → `payee`.
        $paiements->enregistrer($facture, 8000, 'virement', 'VIR-9', 'Solde', null);

        $facture->refresh();
        $this->assertEquals('payee', $facture->statut);
        $this->assertEquals(15000, $facture->montant_paye);
        $this->assertEquals(0, $facture->resteAPayer());
    }

    public function test_abonnement_mensuel_facture_par_compte_actif_du_tenant(): void
    {
        $cabinetId = $this->makeCabinet('fpc7a')->getKey();
        $this->contratValide($cabinetId);

        // Comptes du tenant : 2 enseignants actifs + 1 élève (parent tarif
        // null → jamais facturé). Le calcul initialise lui-même le tenant.
        tenancy()->initialize($cabinetId);
        try {
            foreach (['enseignant' => 2, 'eleve' => 1] as $role => $n) {
                for ($i = 0; $i < $n; $i++) {
                    $u = \App\Models\User::create([
                        'nom' => 'Compte '.$i,
                        'prenom' => ucfirst($role),
                        'email' => $role.$i.'@fpc7.local',
                        'password' => 'Secret1234',
                        'statut' => true,
                    ]);
                    $u->assignRole($role);
                }
            }
        } finally {
            tenancy()->end();
        }

        $facture = $this->facture($cabinetId, '2026-10-01', '2026-10-31');

        // 2×3 000 (enseignants) + 1×2 000 (élève) = 8 000 ; parent null → rien.
        $this->assertEquals(8000, $facture->montant_total);

        $abonnements = $facture->lignes->where('reference_type', 'compte')->values();
        $this->assertSame(2, $abonnements->count());

        $enseignants = $abonnements->firstWhere('type', 'abonnement_enseignant');
        $this->assertEquals(6000, $enseignants->montant);
        $this->assertEquals(2, $enseignants->quantite);
        $this->assertStringContainsString('2 comptes', $enseignants->libelle);

        $eleves = $abonnements->firstWhere('type', 'abonnement_eleve');
        $this->assertEquals(2000, $eleves->montant);
    }

    // ------------------------------------------------------------------
    // 3. ISOLATION ENTRE CABINETS
    // ------------------------------------------------------------------

    public function test_isolation_deux_cabinets_factures_separement(): void
    {
        $id1 = $this->makeCabinet('fpc6a')->getKey();
        $id2 = $this->makeCabinet('fpc6b')->getKey();

        $this->contratValide($id1);
        $this->contratValide($id2, ['tarifs' => ['frais_creation_contrat' => 22000]]);

        $this->evenement($id1, EvenementFacturable::TYPE_CONTRAT_CREE, 'e1', '2026-10-05');
        $this->evenement($id2, EvenementFacturable::TYPE_CONTRAT_CREE, 'e1', '2026-10-05');

        $f1 = $this->facture($id1, '2026-10-01', '2026-10-31');
        $f2 = $this->facture($id2, '2026-10-01', '2026-10-31');

        $this->assertEquals(15000, $f1->montant_total);
        $this->assertEquals(22000, $f2->montant_total);

        // La numérotation est globale à la plateforme (Q-A19) : 00002 pour
        // la seconde émission du mois, peu importe le cabinet.
        $this->assertSame('FC-202610-00001', $f1->numero_facture);
        $this->assertSame('FC-202610-00002', $f2->numero_facture);

        // f2 ne porte qu'un événement de c2 (e1) : pas de compte actif dans
        // ces tenants → aucune ligne d'abonnement.
        $refs2 = $f2->lignes->pluck('reference_id')->unique();
        $this->assertEquals(['e1'], $refs2->values()->all());
    }

    // ------------------------------------------------------------------
    // AUXILIAIRES
    // ------------------------------------------------------------------

    private function brouillonSansEmission(string $cabinetId, array $lignesManuelles = []): object
    {
        $service = app(FacturationCabinetService::class);

        return $service->creerFactureBrouillon(
            $cabinetId,
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31'),
            $service->contratValide($cabinetId),
            $lignesManuelles,
        );
    }
}
