<?php

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodeComptableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'date_debut' => $this->date_debut?->toDateString(),
            'date_fin' => $this->date_fin?->toDateString(),
            'type' => $this->type,
            'statut' => $this->statut,
            'est_ouverte' => $this->estOuverte(),
            'est_cloturee' => $this->estCloturee(),
            'cloturee_par' => $this->cloturee_par,
            'cloturee_par_nom' => $this->clotureur
                ? trim(($this->clotureur->prenom ?? '') . ' ' . ($this->clotureur->nom ?? ''))
                : null,
            'cloturee_at' => $this->cloturee_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}