<?php

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * T7A.9 — Ajustement (prime ou retenue) d'un bulletin.
 */
class BulletinPaieAjustementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'bulletin_paie_id' => (int) $this->bulletin_paie_id,
            'type_ajustement_id' => $this->type_ajustement_id
                ? (int) $this->type_ajustement_id
                : null,
            'type' => $this->type,
            'libelle' => $this->libelle,
            'montant' => (int) $this->montant,
        ];
    }
}