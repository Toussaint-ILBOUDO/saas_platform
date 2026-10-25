<?php

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ajustement (bonus ou débit) d'une facture parent.
 */
class FactureAjustementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'facture_id' => (int) $this->facture_id,
            'type_ajustement_id' => $this->type_ajustement_id
                ? (int) $this->type_ajustement_id
                : null,
            'type' => $this->type,
            'libelle' => $this->libelle,
            'montant' => (int) $this->montant,
        ];
    }
}
