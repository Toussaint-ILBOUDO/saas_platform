<?php

namespace Tests\Feature;

use App\Models\AffectationEnseignant;
use App\Models\BulletinPaie;
use App\Models\CahierTexte;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Facture;
use App\Models\Matiere;
use App\Models\Notification;
use App\Models\ObjectifPedagogique;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;
use App\Models\TypeCours;
use App\Models\User;
use App\Modules\Finance\Services\BulletinPaieCalculationService;
use App\Modules\Finance\Services\BulletinPaieGenerationService;
use App\Modules\Finance\Services\BulletinPaieValidationService;
use App\Modules\Finance\Services\FacturationService;
use App\Modules\Finance\Services\PeriodeComptableService;
use App\Modules\Pedagogie\Services\CahierTexteService;
use App\Modules\Pedagogie\Services\ObjectifPedagogiqueService;
use App\Modules\Pedagogie\Services\RapportMensuelService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * T7A.0 — Règles métier du socle Finance (D-048 à D-051).
 *
 * Ces tests verrouillent les invariants qui n'étaient PAS garantis par KEduc :
 *   - D-048 : une seule chaîne de paie (bulletin), plus de paiement enseignant ;
 *   - D-049 : les heures sont ventilées par matière et ne sont jamais doublées ;
 *   - D-050 : un objectif par (élève, période comptable, enseignant) ;
 *   - D-051 : seul un rapport VALIDÉ facture, et une période close refuse
 *             toute écriture.
 *
 * Ils s'exécutent au niveau des **services** (et non des contrôleurs) : c'est
 * le niveau qui doit être tenable, sinon l'API ou un job pourrait contourner
 * une règle vérifiée uniquement dans un formulaire web.
 */
class ReglesMetierFinanceTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private FacturationService $serviceFacturation;

    private PeriodeComptableService $servicePeriode;

    private BulletinPaieCalculationService $serviceBulletin;

    private RapportMensuelService $serviceRapport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceFacturation = app(FacturationService::class);
        $this->servicePeriode = app(PeriodeComptableService::class);
        $this->serviceBulletin = app(BulletinPaieCalculationService::class);
        $this->serviceRapport = app(RapportMensuelService::class);
    }

    /*
    |--------------------------------------------------------------------------
    | D-049 — La ventilation remplace le cumul global
    |--------------------------------------------------------------------------
    */

    public function test_les_heures_sont_ventilees_par_matiere_et_non_doublees(): void
    {
        $c = $this->contexte();

        /*
        | Un enseignant donne DEUX matières au même élève : 4 h de maths et
        | 2 h de français sur la période. Le cumul global vaut 6 h.
        |
        | KEduc indexait le rapport par enseignant puis bouclait sur les
        | AFFECTATIONS en réappliquant le cumul à chaque taux : le total
        | devenait 4 h x taux_maths + 6 h x taux_francais — une facture fausse
        | ET une paie fausse. La ventilation donne
        | 4 h x taux_maths + 2 h x taux_francais.
        */
        $rapport = $this->deposerRapport($c, [
            'maths' => [['heures' => 2], ['heures' => 2]],
            'francais' => [['heures' => 2]],
        ]);

        $this->assertSame(6.0, (float) $rapport->volume_horaire_cumule);

        $this->assertCount(
            2,
            $rapport->lignes,
            'Le rapport porte une ligne de ventilation par matière teachée.'
        );

        $parMatiere = $rapport->lignes->keyBy('matiere_id');

        $maths = $parMatiere[$c['matieres']['maths']->id];
        $francais = $parMatiere[$c['matieres']['francais']->id];

        $this->assertSame(4.0, (float) $maths->nombre_heures);
        $this->assertSame(2, $maths->nombre_seances);

        $this->assertSame(2.0, (float) $francais->nombre_heures);
        $this->assertSame(1, $francais->nombre_seances);

        tenancy()->end();
    }

    public function test_la_facture_retient_exactement_les_heures_validees(): void
    {
        $c = $this->contexte();

        $this->deposerRapport($c, [
            'maths' => [['heures' => 4]],
            'francais' => [['heures' => 2]],
        ]);

        $facture = $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );

        // 4 h x 3000 (maths) + 2 h x 2000 (français) = 16 000
        $this->assertSame(16000, (int) $facture->montant_total);
        $this->assertSame(6.0, (float) $facture->volume_horaire_total);

        $this->assertCount(2, $facture->lignes);
        $this->assertSame(
            16000,
            (int) $facture->lignes->sum('montant'),
            'Le total des lignes doit égaler le montant total de la facture.'
        );

        tenancy()->end();
    }

    public function test_le_bulletin_paie_utilise_la_meme_ventilation_que_la_facture(): void
    {
        $c = $this->contexte();

        $this->deposerRapport($c, [
            'maths' => [['heures' => 4]],
            'francais' => [['heures' => 2]],
        ]);

        $facture = $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );

        $calcul = $this->serviceBulletin->calculerLignes(
            $c['enseignant'],
            $c['periode']->id
        );

        // Source unique de vérité : ce que paie l'enseignant est exactement ce
        // que facture le parent.
        $this->assertSame((int) $facture->montant_total, (int) $calcul['montant_brut']);
        $this->assertSame(6.0, (float) $calcul['total_heures']);

        tenancy()->end();
    }

    public function test_une_affectation_inactive_ne_produit_aucune_ligne_facturable(): void
    {
        $c = $this->contexte();

        $this->deposerRapport($c, [
            'maths' => [['heures' => 4]],
            'francais' => [['heures' => 2]],
        ]);

        // L'affectation de français est terminée : ces heures ne sont plus dues.
        $c['affectations']['francais']->update(['statut' => 'termine']);

        $facture = $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );

        $this->assertCount(1, $facture->lignes);
        $this->assertSame(12000, (int) $facture->montant_total);

        tenancy()->end();
    }

    /*
    |--------------------------------------------------------------------------
    | D-051 — Seul un rapport VALIDÉ facture
    |--------------------------------------------------------------------------
    */

    public function test_un_rapport_soumis_ne_debloque_pas_la_facture(): void
    {
        $c = $this->contexte();

        $this->deposerRapport($c, [
            'maths' => [['heures' => 4]],
        ], valider: false);

        $this->expectException(ValidationException::class);

        $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );
    }

    public function test_une_seule_facture_par_contrat_et_par_periode(): void
    {
        $c = $this->contexte();

        $this->deposerRapport($c, [
            'maths' => [['heures' => 4]],
        ]);

        $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );

        $this->expectException(ValidationException::class);

        $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );
    }

    public function test_le_rejet_exige_un_motif_et_le_rapport_reste_re_soumissible(): void
    {
        $c = $this->contexte();

        $rapport = $this->deposerRapport($c, [
            'maths' => [['heures' => 4]],
        ], valider: false);

        $this->serviceRapport->rejeter($rapport, 'Séance du 05 absente du cahier de texte.');

        $rapport = $rapport->fresh();

        $this->assertSame('rejete', $rapport->statut);
        $this->assertSame('Séance du 05 absente du cahier de texte.', $rapport->motif_rejet);

        // Un rapport rejeté ne facture pas.
        try {
            $this->serviceFacturation->generer(
                ['contrat_cours_id' => $c['contrat']->id],
                $c['periode']->id
            );

            $this->fail('Un rapport rejeté ne doit pas débloquer la facturation.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }

        // Re-soumission : le statut repart de « soumis », le motif est effacé.
        $rapport = $this->serviceRapport->resoumettre($rapport);

        $this->assertSame('soumis', $rapport->statut);
        $this->assertNull($rapport->motif_rejet);

        // Puis la validation débloque enfin la facturation.
        $this->serviceRapport->valider($rapport);

        $this->assertSame('valide', $rapport->fresh()->statut);

        $facture = $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );

        $this->assertSame(12000, (int) $facture->montant_total);

        tenancy()->end();
    }

    public function test_un_rapport_valide_ne_peut_plus_etre_supprime(): void
    {
        $c = $this->contexte();

        $rapport = $this->deposerRapport($c, [
            'maths' => [['heures' => 4]],
        ]);

        $this->assertSame('valide', $rapport->statut);

        // Il a produit (ou peut produire) une facture : le supprimer
        // désynchroniserait la facturation et la paie.
        $this->expectException(ValidationException::class);

        $this->serviceRapport->supprimer($rapport->fresh());
    }

    public function test_un_rapport_valide_est_trace_avec_son_administrateur(): void
    {
        $c = $this->contexte();

        $rapport = $this->deposerRapport($c, [
            'maths' => [['heures' => 4]],
        ], valider: false);

        $this->actingAs($c['admin']);

        $this->serviceRapport->valider($rapport->fresh());

        $rapport = $rapport->fresh();

        $this->assertNotNull($rapport->date_validation);
        $this->assertSame($c['admin']->id, (int) $rapport->valide_par);
    }

    /*
    |--------------------------------------------------------------------------
    | D-051 — Le gel des périodes
    |--------------------------------------------------------------------------
    */

    public function test_une_periode_close_refuse_la_facturation(): void
    {
        $c = $this->contexte();

        $this->deposerRapport($c, [
            'maths' => [['heures' => 4]],
        ]);

        $this->servicePeriode->close($c['periode']);

        $this->assertTrue($c['periode']->fresh()->estCloturee());

        try {
            $this->serviceFacturation->generer(
                ['contrat_cours_id' => $c['contrat']->id],
                $c['periode']->id
            );

            $this->fail('La facturation doit être refusée sur une période close.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString(
                'clôturée',
                implode(' ', Arr::flatten($e->errors()))
            );
        }

        tenancy()->end();
    }

    public function test_une_periode_close_refuse_la_saisie_de_cahier_de_texte(): void
    {
        $c = $this->contexte();

        $this->servicePeriode->close($c['periode']);

        $service = app(CahierTexteService::class);

        // La clé est épinglée : le refus doit venir du gel de la période, pas
        // d'un contrôle de propriété qui masquerait la cause réelle.
        try {
            $service->create($c['enseignantUser'], [
                'affectation_enseignant_id' => $c['affectations']['maths']->id,
                'date_seance' => '2026-03-10',
                'heure_debut' => '09:00',
                'heure_fin' => '11:00',
                'contenu_cours' => 'Fractions',
            ]);

            $this->fail('La saisie aurait dû être refusée sur une période close.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('periode', $e->errors());
            $this->assertStringContainsString('clôturée', $e->errors()['periode'][0]);
        }
    }

    public function test_une_date_hors_periode_ouverte_est_refusee(): void
    {
        $c = $this->contexte();

        $service = app(CahierTexteService::class);

        // Aucune période comptable ne couvre le 15 juin 2026 : la séance ne
        // pourrait jamais être rattachée à un rapport, donc ni facturée ni payée.
        try {
            $service->create($c['enseignantUser'], [
                'affectation_enseignant_id' => $c['affectations']['maths']->id,
                'date_seance' => '2026-06-15',
                'heure_debut' => '09:00',
                'heure_fin' => '11:00',
                'contenu_cours' => 'Fractions',
            ]);

            $this->fail('La saisie aurait dû être refusée hors de toute période.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('date', $e->errors());
            $this->assertStringContainsString('Aucune période comptable', $e->errors()['date'][0]);
        }
    }

    public function test_la_cloture_et_la_reouverture_sont_tracees(): void
    {
        $c = $this->contexte();

        $this->actingAs($c['admin']);

        $this->servicePeriode->close($c['periode']);

        $periode = $c['periode']->fresh();

        $this->assertSame($c['admin']->id, (int) $periode->cloturee_par);
        $this->assertNotNull($periode->cloturee_at);

        // Clôturer deux fois est refusé.
        try {
            $this->servicePeriode->close($periode);
            $this->fail('Une période déjà close ne doit pas être clôturée deux fois.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }

        $this->servicePeriode->reopen($periode);

        $periode = $periode->fresh();

        $this->assertTrue($periode->estOuverte());
        $this->assertNull($periode->cloturee_par);
        $this->assertNull($periode->cloturee_at);

        tenancy()->end();
    }

    public function test_une_periode_avec_des_factures_ne_se_reouvre_pas(): void
    {
        $c = $this->contexte();

        $this->deposerRapport($c, [
            'maths' => [['heures' => 4]],
        ]);

        $facture = $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );

        $this->servicePeriode->close($c['periode']);

        $this->expectException(ValidationException::class);

        $this->servicePeriode->reopen($c['periode']->fresh());

        $this->assertTrue(Facture::whereKey($facture->id)->exists());
    }

    public function test_les_periodes_ne_se_chevauchent_pas(): void
    {
        $c = $this->contexte();

        $this->expectException(ValidationException::class);

        $this->servicePeriode->create([
            'label' => 'Mars (chevauchement)',
            'date_debut' => '2026-03-10',
            'date_fin' => '2026-03-20',
            'type' => 'mensuel',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | D-050 — Objectifs pédagogiques
    |--------------------------------------------------------------------------
    */

    public function test_un_objectif_par_eleve_periode_et_enseignant(): void
    {
        $c = $this->contexte();

        $service = app(ObjectifPedagogiqueService::class);

        $donnees = [
            'eleve_id' => $c['eleve']->id,
            'periode_id' => $c['periode']->id,
            'moyenne_visee' => 14,
            'matieres' => [
                ['matiere_id' => $c['matieres']['maths']->id, 'moyenne_visee' => 15],
            ],
        ];

        $service->create($donnees);

        $this->assertSame(1, ObjectifPedagogique::count());

        // Le même objectif pour la même période et le même enseignant est refusé.
        try {
            $service->create($donnees);
            $this->fail('Un doublon d\'objectif doit être refusé.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }

        tenancy()->end();
    }

    public function test_un_objectif_ne_peut_pas_etre_saisi_sur_une_periode_close(): void
    {
        $c = $this->contexte();

        $this->servicePeriode->close($c['periode']);

        $service = app(ObjectifPedagogiqueService::class);

        $this->expectException(ValidationException::class);

        $service->create([
            'eleve_id' => $c['eleve']->id,
            'periode_id' => $c['periode']->id,
            'moyenne_visee' => 14,
            'matieres' => [
                ['matiere_id' => $c['matieres']['maths']->id, 'moyenne_visee' => 15],
            ],
        ]);
    }

    public function test_un_objectif_dune_periode_close_ne_peut_pas_etre_deplace(): void
    {
        $c = $this->contexte();

        $service = app(ObjectifPedagogiqueService::class);

        $objectif = $service->create([
            'eleve_id' => $c['eleve']->id,
            'periode_id' => $c['periode']->id,
            'moyenne_visee' => 14,
            'matieres' => [
                ['matiere_id' => $c['matieres']['maths']->id, 'moyenne_visee' => 15],
            ],
        ]);

        $avril = PeriodeComptable::create([
            'label' => 'Avril 2026',
            'date_debut' => '2026-04-01',
            'date_fin' => '2026-04-30',
            'type' => 'mensuel',
            'statut' => PeriodeComptable::OUVERTE,
        ]);

        $this->servicePeriode->close($c['periode']);

        /*
        | Indiquer une autre période (ouverte) ne doit pas permettre de vider
        | une période close : le gel protège la période d'origine, pas
        | seulement la période d'arrivée.
        */
        $this->expectException(ValidationException::class);

        $service->update($objectif, ['periode_id' => $avril->id]);
    }

    public function test_un_objectif_peut_etre_recorrige_vers_une_autre_periode_ouverte(): void
    {
        $c = $this->contexte();

        $service = app(ObjectifPedagogiqueService::class);

        $objectif = $service->create([
            'eleve_id' => $c['eleve']->id,
            'periode_id' => $c['periode']->id,
            'moyenne_visee' => 14,
            'matieres' => [
                ['matiere_id' => $c['matieres']['maths']->id, 'moyenne_visee' => 15],
            ],
        ]);

        $avril = PeriodeComptable::create([
            'label' => 'Avril 2026',
            'date_debut' => '2026-04-01',
            'date_fin' => '2026-04-30',
            'type' => 'mensuel',
            'statut' => PeriodeComptable::OUVERTE,
        ]);

        // Correction d'une saisie faite dans la mauvaise période.
        $service->update($objectif, [
            'periode_id' => $avril->id,
            'moyenne_visee' => 15,
        ]);

        $objectif = $objectif->fresh();

        $this->assertSame($avril->id, $objectif->periode_id);
        $this->assertSame(15, (int) $objectif->moyenne_visee);

        tenancy()->end();
    }

    public function test_la_date_dune_seance_ne_peut_pas_etre_deplacee(): void
    {
        $c = $this->contexte();

        // La modification d'une séance n'est permise que le jour même.
        $cahier = CahierTexte::create([
            'affectation_enseignant_id' => $c['affectations']['maths']->id,
            'date_seance' => now()->toDateString(),
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'duree_heures' => 2,
            'contenu_cours' => 'Fractions',
        ]);

        $service = app(CahierTexteService::class);

        // `date_seance` estfillable : sans ce garde, un appelant autre que le
        // formulaire web pouvait déplacer la séance vers une période close.
        try {
            $service->update($c['enseignantUser'], $cahier, [
                'date_seance' => now()->addDay()->toDateString(),
                'heure_debut' => '09:00',
                'heure_fin' => '11:00',
                'contenu_cours' => 'Fractions',
            ]);

            $this->fail('La date de la séance aurait dû être refusée.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('date_seance', $e->errors());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Numérotation atomique
    |--------------------------------------------------------------------------
    */

    public function test_les_numeros_de_facture_ne_se_recolent_pas_apres_suppression(): void
    {
        $c = $this->contexte();

        $this->deposerRapport($c, ['maths' => [['heures' => 4]]]);
        $deuxieme = $this->secondContrat($c);
        $this->deposerRapport($deuxieme, ['maths' => [['heures' => 4]]]);

        $facture1 = $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );

        $facture2 = $this->serviceFacturation->generer(
            ['contrat_cours_id' => $deuxieme['contrat']->id],
            $c['periode']->id
        );

        $this->assertNotSame($facture1->numero_facture, $facture2->numero_facture);

        $numeroVivant = $facture2->numero_facture;

        $facture1->delete();

        /*
        | Le numéro est calculé sur le MAXIMUM existant, pas sur `count() + 1` :
        | après suppression de FAC-…-00001, `count()` donnerait 1 et le numéro
        | suivant VIENDRAIT RECOLLER celui de la facture vivante. Les documents
        | déjà imprimés auraient alors deux numéros.
        */
        $facture3 = $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );

        $this->assertNotSame($numeroVivant, $facture3->numero_facture);
        $this->assertGreaterThan($numeroVivant, $facture3->numero_facture);

        tenancy()->end();
    }

    /*
    |--------------------------------------------------------------------------
    | Règlements
    |--------------------------------------------------------------------------
    */

    public function test_une_facture_ne_peut_pas_etre_reglee_deux_fois(): void
    {
        $c = $this->contexte();

        $this->deposerRapport($c, ['maths' => [['heures' => 4]]]);

        $facture = $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );

        $this->serviceFacturation->marquerPaye($facture, [
            'date_paiement' => '2026-03-31',
            'mode_paiement' => 'especes',
        ]);

        $this->assertTrue($facture->fresh()->estPayee());

        $this->expectException(ValidationException::class);

        $this->serviceFacturation->marquerPaye($facture->fresh(), [
            'date_paiement' => '2026-04-02',
            'mode_paiement' => 'especes',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | D-052 — Fin du cycle de paie enseignant
    |--------------------------------------------------------------------------
    */

    public function test_le_cycle_du_bulletin_se_clot_par_la_confirmation_de_reception(): void
    {
        $c = $this->contexte();
        $bulletin = $this->bulletin($c);
        $service = app(BulletinPaieValidationService::class);

        // Cycle : consulté → validé → versé.
        $service->consulter($bulletin);
        $service->valider($bulletin->fresh());
        $service->payer($bulletin->fresh(), [
            'date_paiement' => '2026-04-02',
            'mode_paiement' => 'virement',
            'reference_paiement' => 'VIR-2026-0042',
        ]);

        $bulletin = $bulletin->fresh();

        $this->assertTrue($bulletin->estVerse());

        /*
        | Le versement est hors plateforme : tant que l'enseignant n'a pas
        | confirmé la réception, le bulletin n'est pas « terminé » et
        | l'administration doit voir qu'il est en attente.
        */
        $this->assertFalse($bulletin->estRecu());
        $this->assertTrue($bulletin->enAttenteReception());

        $this->actingAs($c['enseignantUser']);

        $service->confirmerReception($bulletin);

        $bulletin = $bulletin->fresh();

        $this->assertTrue($bulletin->estRecu());
        $this->assertFalse($bulletin->enAttenteReception());
        $this->assertNotNull($bulletin->date_reception);
        $this->assertSame($c['enseignantUser']->id, (int) $bulletin->recu_par);

        tenancy()->end();
    }

    public function test_on_ne_peut_pas_confirmer_reception_tant_que_le_paiement_nest_pas_enregistre(): void
    {
        $c = $this->contexte();
        $bulletin = $this->bulletin($c);

        $service = app(BulletinPaieValidationService::class);

        // Un bulletin seulement « généré » n'a pas encore été payé.
        $this->expectException(ValidationException::class);

        $service->confirmerReception($bulletin);
    }

    public function test_la_reception_ne_se_confirme_quune_seule_fois(): void
    {
        $c = $this->contexte();
        $bulletin = $this->bulletin($c);

        $service = app(BulletinPaieValidationService::class);

        $service->consulter($bulletin);
        $service->valider($bulletin->fresh());
        $service->payer($bulletin->fresh(), [
            'date_paiement' => '2026-04-02',
            'mode_paiement' => 'especes',
        ]);

        $this->actingAs($c['enseignantUser']);

        $service->confirmerReception($bulletin->fresh());

        $this->expectException(ValidationException::class);

        $service->confirmerReception($bulletin->fresh());
    }

    public function test_un_nouveau_versement_remet_la_reception_a_zero(): void
    {
        $c = $this->contexte();
        $bulletin = $this->bulletin($c);

        $service = app(BulletinPaieValidationService::class);

        $service->consulter($bulletin);
        $service->valider($bulletin->fresh());
        $service->payer($bulletin->fresh(), [
            'date_paiement' => '2026-04-02',
            'mode_paiement' => 'especes',
        ]);

        $this->actingAs($c['enseignantUser']);
        $service->confirmerReception($bulletin->fresh());

        $this->assertTrue($bulletin->fresh()->estRecu());

        // Un second versement (erreur de saisie corrigée) ne doit pas hériter
        // de la confirmation du précédent : l'enseignant doit recevoir le
        // nouvel argent pour pouvoir le confirmer.
        $bulletin->update(['statut' => 'valide']);

        $service->payer($bulletin->fresh(), [
            'date_paiement' => '2026-04-05',
            'mode_paiement' => 'especes',
        ]);

        $bulletin = $bulletin->fresh();

        $this->assertNull($bulletin->date_reception);
        $this->assertNull($bulletin->recu_par);
        $this->assertTrue($bulletin->enAttenteReception());
    }

    public function test_la_contestation_exige_un_motif_categorise_et_un_detail(): void
    {
        $c = $this->contexte();
        $bulletin = $this->bulletin($c);

        $service = app(BulletinPaieValidationService::class);
        $service->consulter($bulletin);

        // Motif hors liste fermée : refusé.
        try {
            $service->contester($bulletin, 'inventee', 'Detail assez long pour passer.');
            $this->fail('Un motif inconnu doit être refusé.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('motif_contestation', $e->errors());
        }

        // Détail trop court : refusé (l'administration ne comprendrait rien).
        try {
            $service->contester($bulletin, 'heures', 'Trop court.');
            $this->fail('Un détail de moins de 20 caractères doit être refusé.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('commentaire_enseignant', $e->errors());
        }

        $this->assertSame('consulte', $bulletin->fresh()->statut);

        $service->contester(
            $bulletin->fresh(),
            'heures',
            'La séance du 12 mars (2 h) figure sur mon cahier de texte mais '
                . 'est absente des heures retenues sur ce bulletin.'
        );

        $bulletin = $bulletin->fresh();

        $this->assertSame('conteste', $bulletin->statut);
        $this->assertSame('heures', $bulletin->motif_contestation);
        $this->assertSame(
            'Heures retenues incorrectes',
            $bulletin->libelle_motif_contestation
        );
    }

    public function test_les_notifications_de_bulletin_sont_cliquables_et_adaptes_au_role(): void
    {
        $c = $this->contexte();
        $bulletin = $this->bulletin($c);

        $service = app(BulletinPaieValidationService::class);
        $service->consulter($bulletin);

        // L'enseignant reçoit la notification : elle doit ouvrir SON espace.
        $this->actingAs($c['enseignantUser']);

        $notification = Notification::where('type', 'bulletin_paie')
            ->where('user_id', $c['enseignantUser']->id)
            ->latest()
            ->first();

        $this->assertNotNull($notification, 'La consultation doit notifier l\'enseignant.');
        $this->assertSame(
            route('mes-bulletins.show', $bulletin),
            $notification->url
        );

        // L'administration reçoit la confirmation de réception : elle doit
        // ouvrir l'écran d'administration.
        $this->actingAs($c['admin']);

        $this->assertSame(
            route('finance.bulletins-paie.show', $bulletin),
            Notification::where('type', 'bulletin_paie')
                ->where('user_id', $c['admin']->id)
                ->latest()
                ->first()
                ?->url
        );

        tenancy()->end();
    }

    public function test_la_confirmation_de_reception_notifie_l_administration(): void
    {
        $c = $this->contexte();
        $bulletin = $this->bulletin($c);

        $service = app(BulletinPaieValidationService::class);
        $service->consulter($bulletin);
        $service->valider($bulletin->fresh());
        $service->payer($bulletin->fresh(), [
            'date_paiement' => '2026-04-02',
            'mode_paiement' => 'especes',
        ]);

        $this->actingAs($c['enseignantUser']);

        $service->confirmerReception($bulletin->fresh());

        $this->assertTrue(
            Notification::where('type', 'bulletin_paie')
                ->where('user_id', $c['admin']->id)
                ->where('titre', 'Paiement réceptionné')
                ->exists(),
            'L\'administration doit être prévenue que le paiement est réceptionné.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Cabinet + fixture métier complète : un parent, un élève, un contrat,
     * un enseignant avec deux matières, une période ouverte.
     */
    private function contexte(): array
    {
        $cabinet = $this->makeCabinet('regles');
        $this->initCabinet($cabinet);

        $admin = User::where('email', 'admin@regles.local')->first();

        $parent = User::create([
            'nom' => 'Parent',
            'prenom' => 'Paul',
            'email' => 'parent@regles.test',
            'password' => 'secret',
        ]);

        $classe = Classe::create(['nom' => '6e A', 'sigle' => '6A']);

        $eleve = Eleve::create([
            'parent_id' => $parent->id,
            'classe_id' => $classe->id,
            'ecole' => 'Collège central',
        ]);

        $typeCours = TypeCours::create(['libelle' => 'À domicile']);

        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $typeCours->id,
            'date_debut' => '2026-03-01',
            'statut' => 'actif',
        ]);

        $enseignantUser = User::create([
            'nom' => 'Diallo',
            'prenom' => 'Awa',
            'email' => 'awa@regles.test',
            'password' => 'secret',
        ]);

        // Le rôle conditionne l'URL de notification et les policies.
        $enseignantUser->assignRole('enseignant');

        $enseignant = EnseignantProfil::create([
            'user_id' => $enseignantUser->id,
        ]);

        $matieres = [
            'maths' => Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']),
            'francais' => Matiere::create(['nom' => 'Français', 'sigle' => 'FR']),
        ];

        $affectations = [
            'maths' => $this->affecter($contrat, $enseignant, $matieres['maths']),
            'francais' => $this->affecter($contrat, $enseignant, $matieres['francais'], taux: 2000),
        ];

        $periode = PeriodeComptable::create([
            'label' => 'Mars 2026',
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-31',
            'type' => 'mensuel',
            'statut' => PeriodeComptable::OUVERTE,
        ]);

        $this->actingAs($enseignantUser);

        return compact(
            'cabinet',
            'admin',
            'parent',
            'eleve',
            'contrat',
            'enseignant',
            'enseignantUser',
            'matieres',
            'affectations',
            'periode'
        );
    }

    /**
     * Un second élève (contrat + affectation maths) pour obtenir une seconde
     * facture sur la même période.
     */
    private function secondContrat(array $c): array
    {
        $parent = User::create([
            'nom' => 'Parent',
            'prenom' => 'Sara',
            'email' => 'sara@regles.test',
            'password' => 'secret',
        ]);

        $eleve = Eleve::create([
            'parent_id' => $parent->id,
            'classe_id' => $c['eleve']->classe_id,
            'ecole' => 'Collège central',
        ]);

        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $c['contrat']->type_cours_id,
            'date_debut' => '2026-03-01',
            'statut' => 'actif',
        ]);

        $c['contrat'] = $contrat;
        $c['affectations'] = [
            'maths' => $this->affecter($contrat, $c['enseignant'], $c['matieres']['maths']),
        ];

        return $c;
    }

    /**
     * Amène le dossier jusqu'au bulletin de paie : rapport validé, facture
     * générée, bulletins de paie produits par l'administration.
     */
    private function bulletin(array $c): BulletinPaie
    {
        $this->deposerRapport($c, ['maths' => [['heures' => 4]]]);

        $this->serviceFacturation->generer(
            ['contrat_cours_id' => $c['contrat']->id],
            $c['periode']->id
        );

        $bulletins = app(BulletinPaieGenerationService::class)
            ->generer($c['periode']->id);

        $this->assertNotEmpty($bulletins, 'Un bulletin doit être généré.');

        return BulletinPaie::where('periode_id', $c['periode']->id)
            ->where('enseignant_id', $c['enseignant']->id)
            ->firstOrFail();
    }

    private function affecter(
        ContratCours $contrat,
        EnseignantProfil $enseignant,
        Matiere $matiere,
        int $taux = 3000
    ): AffectationEnseignant {
        return AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $enseignant->id,
            'matiere_id' => $matiere->id,
            'taux_horaire_enseignant' => $taux,
            'date_affectation' => '2026-03-01',
            'statut' => 'actif',
        ]);
    }

    /**
     * Saisit les séances dans le cahier de texte puis dépose le rapport via le
     * service (donc avec ventilation persistée).
     *
     * @param  array<string, array<int, array{heures: float}>>  $seances
     *      Clé = nom de l'affectation dans le contexte ; une entrée par séance.
     */
    private function deposerRapport(
        array $c,
        array $seances,
        bool $valider = true
    ): RapportMensuelEnseignant {
        $jour = 2;

        foreach ($seances as $matiere => $liste) {
            foreach ($liste as $seance) {
                $heures = (float) $seance['heures'];

                if ($heures <= 0) {
                    continue;
                }

                CahierTexte::create([
                    'affectation_enseignant_id' => $c['affectations'][$matiere]->id,
                    'date_seance' => sprintf('2026-03-%02d', $jour++),
                    'heure_debut' => '09:00',
                    'heure_fin' => sprintf('%02d:00', 9 + (int) $heures),
                    'duree_heures' => $heures,
                    'contenu_cours' => 'Cours du ' . $jour,
                ]);
            }
        }

        $rapport = $this->serviceRapport->generate(
            [
                'contrat_cours_id' => $c['contrat']->id,
                'periode_id' => $c['periode']->id,
            ],
            $c['enseignant']->id
        );

        return $valider ? $this->serviceRapport->valider($rapport) : $rapport;
    }
}