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
     * - affectations concernées
     * - cahiers de texte
     * - nombre de séances
     * - volume horaire
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

        ];
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