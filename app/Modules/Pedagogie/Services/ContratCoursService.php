<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\ContratCours;
use App\Models\EnseignantProfil;
use App\Models\EnseignantMatiere;
use Illuminate\Support\Facades\DB;
use App\Modules\Systeme\Services\NotificationDispatcher;

class ContratCoursService
{
    public function create(array $data): ContratCours
    {
        return DB::transaction(function () use ($data) {

            $notifier = app(NotificationDispatcher::class);

            // ======================
            // 1. CREATE CONTRAT
            // ======================
            $contrat = ContratCours::create([
                'eleve_id' => $data['eleve_id'],
                'type_cours_id' => $data['type_cours_id'],
                'date_debut' => $data['date_debut'],
                'date_fin' => $data['date_fin'] ?? null,
                'autres_frais_suivi' => $data['autres_frais_suivi'] ?? 0,
                'notes_admin' => $data['notes_admin'] ?? null,
                'statut' => 'actif',
            ]);

            // ======================
            // 2. NOTIF CONTRAT CRÉÉ
            // ======================
            if ($contrat->eleve?->parent_id) {
                $notifier->contractCreated($contrat);
            }

            $parentNotified = false;

            // ======================
            // 3. AFFECTATIONS ENSEIGNANTS
            // ======================
            foreach ($data['affectations'] as $aff) {

                // ======================
                // 3.1 VALIDATION BUSINESS RULE
                // ======================
                $valid = EnseignantMatiere::where([
                    'enseignant_profil_id' => $aff['enseignant_id'],
                    'matiere_id' => $aff['matiere_id'],
                ])->exists();

                if (!$valid) {
                    throw new \InvalidArgumentException(
                        "Cet enseignant n'est pas assigné à cette matière."
                    );
                }

                // ======================
                // 3.2 CREATE AFFECTATION
                // ======================
                $affectation = $contrat->affectations()->create([
                    'enseignant_id' => $aff['enseignant_id'],
                    'matiere_id' => $aff['matiere_id'],
                    'taux_horaire_enseignant' => $aff['taux_horaire_enseignant'] ?? 0,
                    'nombre_heures_prevues' => $aff['nombre_heures_prevues'] ?? 0,
                    'date_affectation' => $aff['date_affectation'] ?? now(),
                ]);

                // ======================
                // 3.3 ENSEIGNANT INFO
                // ======================
                $enseignant = EnseignantProfil::find($aff['enseignant_id']);

                // ======================
                // 3.4 NOTIF ENSEIGNANT
                // ======================
                if ($enseignant?->user_id) {
                    $notifier->teacherAssigned($enseignant->user_id, $contrat);
                }

                // ======================
                // 3.5 NOTIF PARENT (UNE SEULE FOIS)
                // ======================
                if (!$parentNotified) {
                    $notifier->parentTeacherAssigned($contrat);
                    $parentNotified = true;
                }
            }

            // ======================
            // 4. RETURN CONTRAT COMPLET
            // ======================
            return $contrat->load([
                'eleve',
                'typeCours',
                'affectations.enseignant',
                'affectations.matiere',
            ]);
        });
    }
}