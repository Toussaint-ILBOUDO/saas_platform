<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\AffectationEnseignant;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;
use App\Modules\Finance\Services\GardePeriodeOuverte;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RapportMensuelService
{
    public function __construct(
        protected RapportMensuelCalculator $calculator,
        protected NotificationDispatcher $notificationDispatcher,
        protected GardePeriodeOuverte $garde
    ) {
    }


    /**
     * Création du rapport mensuel.
     *
     * Le calcul pédagogique provient
     * uniquement du Calculator afin
     * d'avoir le même résultat que le preview.
     */
    public function generate(
        array $data,
        int $enseignantId
    ): RapportMensuelEnseignant {

        return DB::transaction(function () use (
            $data,
            $enseignantId
        ) {


            $this->ensureReportDoesNotExist(
                $data['contrat_cours_id'],
                $enseignantId,
                $data['periode_id']
            );

            /*
            |--------------------------------------------------------------------------
            | Le contrat doit être celui de l'enseignant
            |--------------------------------------------------------------------------
            |
            | Sans ce contrôle, un enseignant peut déposer un rapport sur le
            | contrat d'un collègue : le Calculator ne ramène alors aucune
            | affectation à son nom et le rapport produit est vide — un
            | dossier sans heures, que l'administration ne peut ni valider
            | ni refuser utilement.
            |
            | Le contrôle est ici, et non dans le formulaire, parce qu'il doit
            | s'appliquer quel que soit le point d'entrée (API, Blade, job).
            |
            */

            $this->ensureContratAffecte(
                $data['contrat_cours_id'],
                $enseignantId
            );

            /*
            |--------------------------------------------------------------------------
            | D-051 — Période ouverte obligatoire
            |--------------------------------------------------------------------------
            |
            | Un rapport déposé sur une période close ne serait jamais validé
            | (gel), donc jamais facturé : mieux vaut refuser le dépôt.
            |
            */

            $periode = PeriodeComptable::findOrFail($data['periode_id']);

            $this->garde->exigerOuverte($periode, 'Le dépôt du rapport mensuel');


            /*
            |--------------------------------------------------------------------------
            | Calcul unique des données pédagogiques
            |--------------------------------------------------------------------------
            */

            $stats = $this->calculator->calculate(

                contratId: $data['contrat_cours_id'],

                enseignantId: $enseignantId,

                periodeId: $data['periode_id']

            );


            /*
            |--------------------------------------------------------------------------
            | Création du rapport
            |--------------------------------------------------------------------------
            */

            $rapport = RapportMensuelEnseignant::create(

                $this->buildReportData(
                    $data,
                    $enseignantId,
                    $stats
                )

            );


            /*
            |--------------------------------------------------------------------------
            | D-049 — Persistance de la ventilation
            |--------------------------------------------------------------------------
            |
            | La facture ET le bulletin consomment ces lignes. Sans elles, il
            | n'y a aucune source unique pour les heures par matière : le cumul
            | global ne permet pas de ventiler et donc de payer juste.
            |
            */

            $this->persistVentilation($rapport, $stats['ventilation']);


            /*
            |--------------------------------------------------------------------------
            | Notification administration
            |--------------------------------------------------------------------------
            */

            $this->notifyAdmins($rapport);



            return $rapport->fresh([
                'enseignant.user',
                'contratCours.eleve.user',
                'periode',
                'lignes.affectation.matiere',
            ]);

        });

    }


    /**
     * D-049 — Enregistre la ventilation par affectation/matière.
     *
     * @param  array<int, array<string, mixed>>  $ventilation
     */
    protected function persistVentilation(
        RapportMensuelEnseignant $rapport,
        array $ventilation
    ): void {

        $lignes = [];

        foreach ($ventilation as $ligne) {
            // Une affectation sans aucune séance ne génère pas de ligne : sinon
            // la facture afficherait des lignes à 0 heure.
            if ((int) $ligne['nombre_heures'] <= 0) {
                continue;
            }

            $lignes[] = [
                'rapport_mensuel_enseignant_id' => $rapport->id,
                'affectation_enseignant_id' => $ligne['affectation_enseignant_id'],
                'matiere_id' => $ligne['matiere_id'],
                'nombre_seances' => $ligne['nombre_seances'],
                'nombre_heures' => $ligne['nombre_heures'],
            ];
        }

        $rapport->lignes()->createMany($lignes);
    }


    /**
     * D-051 — Validation par l'administration.
     *
     * C'est LA validation qui débloque la facture parent et le bulletin de paie
     * (voir `FacturationService::verifierPrerequis()` et
     * `BulletinPaieCalculationService::getEnseignantsConcernes()`).
     */
    public function valider(
        RapportMensuelEnseignant $rapport
    ): RapportMensuelEnseignant {

        return DB::transaction(function () use ($rapport) {

            if ($rapport->statut !== 'soumis') {
                throw ValidationException::withMessages([
                    'statut' => 'Seul un rapport soumis peut être validé.',
                ]);
            }

            $this->garde->exigerOuverte(
                PeriodeComptable::findOrFail($rapport->periode_id),
                'La validation du rapport'
            );

            $rapport->update([
                'statut' => 'valide',
                'date_validation' => now(),
                'valide_par' => Auth::id(),
                'motif_rejet' => null,
            ]);

            $this->notificationDispatcher->reportValidated($rapport);

            return $rapport->fresh(['periode', 'lignes.affectation.matiere']);
        });
    }


    /**
     * D-051 — Rejet motivé.
     *
     * KEduc faisait `update(['statut' => 'rejete'])` sans motif ni traçabilité :
     * l'enseignant ne savait pas quoi corriger.
     */
    public function rejeter(
        RapportMensuelEnseignant $rapport,
        string $motif
    ): RapportMensuelEnseignant {

        return DB::transaction(function () use ($rapport, $motif) {

            if ($rapport->statut !== 'soumis') {
                throw ValidationException::withMessages([
                    'statut' => 'Seul un rapport soumis peut être rejeté.',
                ]);
            }

            $rapport->update([
                'statut' => 'rejete',
                'motif_rejet' => $motif,
                'date_validation' => null,
                'valide_par' => null,
            ]);

            $this->notificationDispatcher->reportRejected($rapport, $motif);

            return $rapport->fresh(['periode', 'lignes.affectation.matiere']);
        });
    }


    /**
     * D-051 — Re-soumission après rejet.
     *
     * Le volume horaire et la ventilation sont RECALCULÉS : l'enseignant a pu
     * corriger le cahier de texte entre-temps, et la facture doit refléter les
     * heures réellement validées.
     */
    public function resoumettre(
        RapportMensuelEnseignant $rapport,
        array $data = []
    ): RapportMensuelEnseignant {

        return DB::transaction(function () use ($rapport, $data) {

            if ($rapport->statut !== 'rejete') {
                throw ValidationException::withMessages([
                    'statut' => 'Seul un rapport rejeté peut être re-soumis.',
                ]);
            }

            $this->garde->exigerOuverte(
                PeriodeComptable::findOrFail($rapport->periode_id),
                'La re-soumission du rapport'
            );

            $rapport->fill([
                'statut' => 'soumis',
                'motif_rejet' => null,
            ]);

            $this->recalculer($rapport, $data);

            $this->notifyAdmins($rapport);

            return $rapport->fresh([
                'periode',
                'lignes.affectation.matiere',
            ]);
        });
    }


    /**
     * Correction d'un rapport ENCORE SOUMIS, avant validation.
     *
     * Les heures et la ventilation sont recalculées : le champ
     * `volume_horaire_cumule` reste une donnée dérivée, jamais une saisie.
     */
    public function corriger(
        RapportMensuelEnseignant $rapport,
        array $data = []
    ): RapportMensuelEnseignant {

        return DB::transaction(function () use ($rapport, $data) {

            if (! $rapport->estModifiableParEnseignant()) {
                throw ValidationException::withMessages([
                    'statut' => 'Ce rapport n\'est plus modifiable.',
                ]);
            }

            $this->garde->exigerOuverte(
                PeriodeComptable::findOrFail($rapport->periode_id),
                'La modification du rapport'
            );

            $this->recalculer($rapport, $data);

            return $rapport->fresh([
                'periode',
                'lignes.affectation.matiere',
            ]);
        });
    }


    /**
     * Recalcule les données dérivées et applique les champs saisis.
     */
    private function recalculer(
        RapportMensuelEnseignant $rapport,
        array $data
    ): void {

        $stats = $this->calculator->calculate(
            contratId: $rapport->contrat_cours_id,
            enseignantId: $rapport->enseignant_id,
            periodeId: $rapport->periode_id
        );

        $rapport->fill([
            'volume_horaire_cumule' => $stats['volume_horaire'],
            'bilan_activites' => $stats['bilan'],
        ]);

        if (array_key_exists('reponses', $data)) {
            $rapport->reponses = $data['reponses'] ?? [];
        }

        // Colonnes dépréciées du monolithe web : conservées telles quelles.
        foreach ([
            'point_notes_matieres',
            'point_notes_autres_matieres',
            'difficultes_rencontrees',
            'solutions_trouvees',
            'attentes_parents_eleve',
            'attentes_administration',
            'appreciation_evolution',
            'observations',
        ] as $champ) {
            if (array_key_exists($champ, $data)) {
                $rapport->{$champ} = $data[$champ];
            }
        }

        $rapport->save();

        // On repart de zéro : la ventilation doit correspondre au nouveau relevé.
        $rapport->lignes()->delete();
        $this->persistVentilation($rapport, $stats['ventilation']);
    }


    /**
     * Suppression.
     *
     * Un rapport VALIDÉ est intouchable : ses heures ont déjà été facturées au
     * parent et payées à l'enseignant, la supprimer désynchroniserait les deux.
     */
    public function supprimer(
        RapportMensuelEnseignant $rapport
    ): void {

        if ($rapport->estValide()) {
            throw ValidationException::withMessages([
                'rapport' => 'Un rapport validé ne peut pas être supprimé : il a déjà été facturé.',
            ]);
        }

        $this->garde->exigerOuverte(
            PeriodeComptable::findOrFail($rapport->periode_id),
            'La suppression du rapport'
        );

        $rapport->delete();
    }


    /**
     * Prépare les données finales du rapport.
     *
     * Ici on mélange :
     * - données calculées automatiquement
     * - informations saisies par enseignant
     */
    protected function buildReportData(
        array $data,
        int $enseignantId,
        array $stats
    ): array {


        return [

            'contrat_cours_id'
                => $data['contrat_cours_id'],


            'enseignant_id'
                => $enseignantId,


            'periode_id'
                => $data['periode_id'],


            /*
            |--------------------------------------------------------------------------
            | Données automatiques venant du Calculator
            |--------------------------------------------------------------------------
            */

            'volume_horaire_cumule'
                => $stats['volume_horaire'],


            'bilan_activites'
                => $stats['bilan'],


            /*
            |--------------------------------------------------------------------------
            | Réponses au modèle de rapport (sections / éléments)
            |--------------------------------------------------------------------------
            */

            'reponses'
                => $data['reponses'] ?? [],



            /*
            |--------------------------------------------------------------------------
            | Données saisies par enseignant (colonnes dépréciées — monolithe web)
            |--------------------------------------------------------------------------
            */

            'point_notes_matieres'
                => $data['point_notes_matieres']
                ?? null,


            'point_notes_autres_matieres'
                => $data['point_notes_autres_matieres']
                ?? null,


            'difficultes_rencontrees'
                => $data['difficultes_rencontrees']
                ?? null,


            'solutions_trouvees'
                => $data['solutions_trouvees']
                ?? null,


            'attentes_parents_eleve'
                => $data['attentes_parents_eleve']
                ?? null,


            'attentes_administration'
                => $data['attentes_administration']
                ?? null,


            'appreciation_evolution'
                => $data['appreciation_evolution']
                ?? null,


            'observations'
                => $data['observations']
                ?? null,


            'statut'
                => 'soumis',

        ];

    }




    /**
     * Vérifie qu'un rapport identique
     * n'existe pas déjà.
     */
    protected function ensureReportDoesNotExist(
        int $contratId,
        int $enseignantId,
        int $periodeId
    ): void {


        $exists = RapportMensuelEnseignant::query()

            ->where('contrat_cours_id', $contratId)

            ->where('enseignant_id', $enseignantId)

            ->where('periode_id', $periodeId)

            ->exists();



        if ($exists) {

            throw ValidationException::withMessages([
                'rapport' => 'Un rapport existe déjà pour cette période.',
            ]);

        }

    }

    /**
     * Vérifie que l'enseignant est bien affecté au contrat.
     *
     * Une affectation suffit : le rapport porte sur le contrat, et sa
     * ventilation ne concerne que les matières que l'enseignant y assure.
     */
    protected function ensureContratAffecte(
        int $contratId,
        int $enseignantId
    ): void {

        $affecte = AffectationEnseignant::query()
            ->where('contrat_cours_id', $contratId)
            ->where('enseignant_id', $enseignantId)
            ->exists();

        if (! $affecte) {
            throw ValidationException::withMessages([
                'contrat_cours_id' => 'Ce cours ne fait pas partie de vos affectations.',
            ]);
        }
    }




    /**
     * Notification des administrateurs.
     */
    protected function notifyAdmins(
        RapportMensuelEnseignant $rapport
    ): void {


        $this->notificationDispatcher
            ->reportSubmitted($rapport);

    }

}