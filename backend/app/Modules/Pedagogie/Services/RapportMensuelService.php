<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\RapportMensuelEnseignant;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RapportMensuelService
{
    public function __construct(
        protected RapportMensuelCalculator $calculator,
        protected NotificationDispatcher $notificationDispatcher
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
            | Notification administration
            |--------------------------------------------------------------------------
            */

            $this->notifyAdmins($rapport);



            return $rapport->fresh([
                'enseignant.user',
                'contratCours.eleve.user',
                'periode',
            ]);

        });

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
            | Données saisies par enseignant
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

            throw new RuntimeException(
                "Un rapport existe déjà pour cette période."
            );

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