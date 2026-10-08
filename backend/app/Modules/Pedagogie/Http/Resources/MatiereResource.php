<?php

namespace App\Modules\Pedagogie\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatiereResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'sigle' => $this->sigle,
            'description' => $this->description,
            'actif' => (bool) $this->actif,
            'nb_enseignants' => $this->whenCounted('enseignants'),
            'nb_affectations' => $this->whenCounted('affectations'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}