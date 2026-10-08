<?php

namespace App\Modules\Pedagogie\Http\Resources;

use App\Modules\Pedagogie\Enums\PlanningJourSemaine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un créneau récurrent du planning (T7A.4).
 *
 * Le créneau est toujours présenté rattaché à son contexte complet — élève,
 * matière, enseignant — parce que c'est ce qui le rend lisible : « Mercredi
 * 18h-19h, Mme Traoré, Maths ». Un tableau de créneaux nus n'apprend rien à
 * l'élève comme au parent.
 */
class PlanningCoursResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $affectation = $this->affectation;

        return [
            'id' => $this->id,
            'jour_semaine' => (int) $this->jour_semaine,
            'jour_label' => PlanningJourSemaine::label((int) $this->jour_semaine),
            'heure_debut' => substr((string) $this->heure_debut, 0, 5),
            'heure_fin' => substr((string) $this->heure_fin, 0, 5),
            'tranche_horaire' => substr((string) $this->heure_debut, 0, 5)
                . ' - ' . substr((string) $this->heure_fin, 0, 5),

            'matiere' => $affectation?->matiere ? [
                'id' => $affectation->matiere->id,
                'nom' => $affectation->matiere->nom,
                'sigle' => $affectation->matiere->sigle,
            ] : null,

            'enseignant' => $this->enseignant ? [
                'id' => $this->enseignant->id,
                'nom' => $this->enseignant->user?->nom,
                'prenom' => $this->enseignant->user?->prenom,
            ] : null,

            'eleve' => $affectation?->contrat?->eleve ? [
                'id' => $affectation->contrat->eleve->id,
                'nom' => $affectation->contrat->eleve->user?->nom,
                'prenom' => $affectation->contrat->eleve->user?->prenom,
            ] : null,

            'contrat_cours_id' => $affectation?->contrat_cours_id,
            'affectation_enseignant_id' => $this->affectation_enseignant_id,
        ];
    }
}