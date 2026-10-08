<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\AffectationEnseignant;
use App\Models\CahierTexte;
use App\Models\PeriodeComptable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class RapportMensuelCalculator
{
    /**
     * Calcule toutes les informations pédagogiques
     * d'un contrat pour un enseignant sur une période.
     *
     * Retourne :
     * - période
     * - affectations concernées (actives uniquement)
     * - cahiers de texte
     * - nombre de séances
     * - volume horaire (total)
     * - ventilation : heures et séances par matière (D-049)
     * - bilan automatique
     */
    public function calculate(
        int $contratId,
        int $enseignantId,
        int $periodeId
    ): array {

        $periode = PeriodeComptable::findOrFail($periodeId);


        /*
        |--------------------------------------------------------------------------
        | Récupération des affectations
        |--------------------------------------------------------------------------
        |
        | Un contrat peut avoir plusieurs matières.
        | Exemple :
        |
        | Contrat élève
        |       |
        |       ├── Mathématiques
        |       ├── Français
        |       └── Physique
        |
        | Donc on récupère toutes les affectations
        | du professeur sur ce contrat.
        |
        */

        $affectations = AffectationEnseignant::query()
            ->where('contrat_cours_id', $contratId)
            ->where('enseignant_id', $enseignantId)
            // Une affectation terminée ou suspendue ne produit pas d'heures
            // payables sur la période.
            ->where('statut', 'actif')
            ->with('matiere')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | IDs des affectations
        |--------------------------------------------------------------------------
        */

        $affectationIds = $affectations
            ->pluck('id');


        /*
        |--------------------------------------------------------------------------
        | Récupération des cahiers de texte
        |--------------------------------------------------------------------------
        */

        $cahiers = CahierTexte::query()
            ->whereIn(
                'affectation_enseignant_id',
                $affectationIds
            )
            ->whereBetween(
                'date_seance',
                [
                    $periode->date_debut->toDateString(),
                    $periode->date_fin->toDateString(),
                ]
            )
            ->orderBy('date_seance')
            ->with([
                'affectation.matiere'
            ])
            ->get();


        return [

            'periode' => $periode,


            'affectations' => $affectations,


            'cahiers' => $cahiers,


            'nombre_seances' => $this->countSessions(
                $cahiers
            ),


            'volume_horaire' => $this->calculateHours(
                $cahiers
            ),


            'bilan' => $this->buildBilan(
                $cahiers
            ),

            /*
            |----------------------------------------------------------------------
            | D-049 — ventilation par matière
            |----------------------------------------------------------------------
            |
            | Une entrée par affectation, avec ses propres heures et son nombre de
            | séances. C'est cette structure qui est persistée dans
            | `rapport_mensuel_enseignant_lignes` puis consommée par la facture ET
            | le bulletin : sans elle, les heures d'un enseignant étaient comptées
            | autant de fois qu'il enseignait de matières au même élève.
            |
            */
            'ventilation' => $this->buildVentilation($affectations, $cahiers),
        ];
    }

    /**
     * Répartit les heures et les séances par affectation (donc par matière).
     *
     * Les affectations sans aucune séance sont conservées avec un volume nul :
     * l'enseignant voit ainsi dans son rapport toutes les matières qu'il
     * enseigne, y compris celles où il n'a rien fait sur la période.
     *
     * @param  Collection<AffectationEnseignant>  $affectations
     * @param  Collection<CahierTexte>  $cahiers
     * @return array<int, array{affectation_enseignant_id: int, matiere_id: int, matiere: string|null, nombre_seances: int, nombre_heures: float}>
     */
    public function buildVentilation(Collection $affectations, Collection $cahiers): array
    {
        $parAffectation = $cahiers->groupBy('affectation_enseignant_id');

        // `groupBy()` conserve le type Eloquent, mais la valeur par défaut doit
        // être elle aussi une Collection Eloquent : `calculateHours()` est
        // typé sur `Illuminate\Database\Eloquent\Collection`, et un `collect()`
        // (Support) y provoquerait une TypeError pour toute affectation sans
        // séance.
        $aucuneSeance = new Collection();

        return $affectations
            ->map(function (AffectationEnseignant $affectation) use ($parAffectation, $aucuneSeance) {
                $cahiersAffectation = $parAffectation->get(
                    $affectation->id,
                    $aucuneSeance
                );

                return [
                    'affectation_enseignant_id' => (int) $affectation->id,
                    'matiere_id' => (int) $affectation->matiere_id,
                    'matiere' => $affectation->matiere?->nom,
                    'nombre_seances' => $cahiersAffectation->count(),
                    'nombre_heures' => $this->calculateHours($cahiersAffectation),
                ];
            })
            ->values()
            ->all();
    }


    /**
     * Nombre total de séances réalisées.
     */
    protected function countSessions(
        Collection $cahiers
    ): int {

        return $cahiers->count();
    }



    /**
     * Somme des heures effectuées.
     */
    protected function calculateHours(
        Collection $cahiers
    ): float {

        return round(
            (float) $cahiers->sum('duree_heures'),
            2
        );
    }



    /**
     * Génère automatiquement le bilan.
     *
     * Ce texte pourra être modifié
     * par l'enseignant avant validation.
     */
    protected function buildBilan(
        Collection $cahiers
    ): string {


        if ($cahiers->isEmpty()) {

            return "Aucune séance réalisée pendant cette période.";
        }



        return $cahiers
            ->map(function ($cahier) {


                $matiere = optional(
                    $cahier->affectation?->matiere
                )->nom;


                return sprintf(

                    "- %s%s | %s - %s\n%s",

                    $matiere
                        ? "[{$matiere}] "
                        : "",

                    $cahier->date_seance
                        ->format('d/m/Y'),


                    $cahier->heure_debut
                        ?? '--',


                    $cahier->heure_fin
                        ?? '--',


                    $cahier->contenu_cours

                );


            })
            ->implode("\n\n");
    }
}