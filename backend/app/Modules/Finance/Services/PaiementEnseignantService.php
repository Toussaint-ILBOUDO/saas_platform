<?php

namespace App\Modules\Finance\Services;

use App\Models\PaiementEnseignant;
use App\Models\LignePaiementEnseignant;
use App\Models\EnseignantProfil;
use App\Models\AffectationEnseignant;
use App\Models\PeriodeComptable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Modules\Systeme\Services\NotificationDispatcher;


class PaiementEnseignantService
{
    public function generate(array $data): PaiementEnseignant
    {
        return DB::transaction(function () use ($data) {
            
            $notifier = app(NotificationDispatcher::class);

            $periode = PeriodeComptable::findOrFail(
                $data['periode_id']
            );

            $enseignant = EnseignantProfil::findOrFail(
                $data['enseignant_id']
            );

            $exists = PaiementEnseignant::query()
                ->where('enseignant_id', $data['enseignant_id'])
                ->where('contrat_cours_id', $data['contrat_cours_id'])
                ->where('periode_id', $data['periode_id'])
                ->exists();

            if ($exists) {

                throw ValidationException::withMessages([
                    'paiement' =>
                        'Paiement déjà généré pour cette période.'
                ]);
            }

            $affectations = AffectationEnseignant::query()
                ->where(
                    'enseignant_id',
                    $data['enseignant_id']
                )
                ->where(
                    'contrat_cours_id',
                    $data['contrat_cours_id']
                )
                ->get();

            if ($affectations->isEmpty()) {

                throw ValidationException::withMessages([
                    'enseignant' =>
                        'Aucune affectation trouvée.'
                ]);
            }

            $totalHeures = 0;
            $montantTotal = 0;

            $lignes = [];

            foreach ($affectations as $affectation) {

                $heures = (float)
                    $affectation
                        ->cahiersTexte()
                        ->whereDate(
                            'date_seance',
                            '>=',
                            $periode->date_debut
                        )
                        ->whereDate(
                            'date_seance',
                            '<=',
                            $periode->date_fin
                        )
                        ->sum('duree_heures');

                if ($heures <= 0) {
                    continue;
                }

                $montant =
                    $heures
                    * $affectation->taux_horaire_enseignant;

                $totalHeures += $heures;
                $montantTotal += $montant;

                $lignes[] = [
                    'affectation_enseignant_id'
                        => $affectation->id,

                    'nombre_heures'
                        => $heures,

                    'montant'
                        => $montant,
                ];
            }

            if (empty($lignes)) {

                throw ValidationException::withMessages([
                    'periode' =>
                        'Aucune heure trouvée sur cette période.'
                ]);
            }

            $paiement = PaiementEnseignant::create([

                'enseignant_id'
                    => $data['enseignant_id'],

                'contrat_cours_id'
                    => $data['contrat_cours_id'],

                'periode_id'
                    => $data['periode_id'],

                'total_heures_effectuees'
                    => $totalHeures,

                'montant_total'
                    => $montantTotal,

                'statut'
                    => 'paye',

                'transaction_reference'
                    => $data['transaction_reference']
                        ?? null,

                'date_paiement'
                    => now(),
            ]);

           if ($enseignant->user_id) {

                // enseignant notifié
                $notifier->teacherPaid($paiement);
            }



            foreach ($lignes as $ligne) {

                LignePaiementEnseignant::create([

                    'paiement_enseignant_id'
                        => $paiement->id,

                    'affectation_enseignant_id'
                        => $ligne['affectation_enseignant_id'],

                    'nombre_heures'
                        => $ligne['nombre_heures'],

                    'montant'
                        => $ligne['montant'],
                ]);
            }

            return $paiement->load('lignes');
        });
    }
}