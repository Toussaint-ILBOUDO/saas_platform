<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Enseignant côté public (P2) : profil académique + matières, sans données privées.
 */
class EnseignantPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profil = $this->relationLoaded('enseignantProfil') ? $this->enseignantProfil : null;

        return [
            'id' => $this->id,
            'prenom' => $this->prenom,
            'nom' => mb_strtoupper(mb_substr((string) $this->nom, 0, 1)).'.',
            'nom_complet' => trim(($this->prenom ?? '').' '.($this->nom ?? '')),
            'photo_url' => $this->photo_profil_url,
            'diplome_max' => $profil?->diplome_max,
            'lieu_de_service' => $profil?->lieu_de_service,
            'matieres' => optional($profil)?->matieres
                ->pluck('nom')
                ->sort()
                ->values()
                ->all() ?? [],
        ];
    }
}