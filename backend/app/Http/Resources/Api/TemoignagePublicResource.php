<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Témoignage côté public (P2) : classé par score (R1/B1/R2…), cache FaqService-like.
 */
class TemoignagePublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'contenu' => $this->contenu,
            'role_label' => $this->role_label,
            'anonyme' => (bool) $this->anonyme,
            'auteur' => $this->auteur_display,
            'score' => $this->score,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}