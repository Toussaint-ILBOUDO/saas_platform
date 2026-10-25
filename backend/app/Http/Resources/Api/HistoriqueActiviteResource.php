<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Entrée d'audit (M13) — lecture admin.
 */
class HistoriqueActiviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'description' => $this->description,
            'level' => $this->level,
            'utilisateur' => $this->whenLoaded('utilisateur', fn () => [
                'id' => $this->utilisateur?->id,
                'nom_complet' => trim(($this->utilisateur?->nom ?? '') . ' ' . ($this->utilisateur?->prenom ?? '')),
                'email' => $this->utilisateur?->email,
            ], null),
            'entite' => $this->entite_type !== null ? [
                'type' => class_basename($this->entite_type),
                'id' => $this->entite_id,
            ] : null,
            'contexte' => $this->contexte,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'date' => $this->created_at?->toIso8601String(),
        ];
    }
}
