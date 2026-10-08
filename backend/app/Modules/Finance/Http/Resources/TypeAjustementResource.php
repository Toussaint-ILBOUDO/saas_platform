<?php

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * T7A.9 — Type d'ajustement (crédit/débit) proposé à l'administration.
 */
class TypeAjustementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'libelle' => $this->libelle,
            'direction' => $this->direction,
        ];
    }
}