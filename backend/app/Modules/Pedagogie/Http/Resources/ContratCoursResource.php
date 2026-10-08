<?php

namespace App\Modules\Pedagogie\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContratCoursResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statut' => $this->statut,
            'date_debut' => $this->date_debut?->toDateString(),
            'date_fin' => $this->date_fin?->toDateString(),
            'autres_frais_suivi' => (int) $this->autres_frais_suivi,
            'notes_admin' => $this->notes_admin,

            'eleve' => $this->whenLoaded('eleve', fn () => $this->eleve ? [
                'id' => $this->eleve->id,
                'nom' => $this->eleve->user?->nom,
                'prenom' => $this->eleve->user?->prenom,
                'classe_id' => $this->eleve->classe_id,
            ] : null),

            'type_cours' => $this->whenLoaded('typeCours', fn () => $this->typeCours ? [
                'id' => $this->typeCours->id,
                'code' => $this->typeCours->code,
                'libelle' => $this->typeCours->libelle,
            ] : null),

            'affectations' => AffectationEnseignantResource::collection(
                $this->whenLoaded('affectations')
            ),

            'nb_affectations' => $this->whenCounted('affectations'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}