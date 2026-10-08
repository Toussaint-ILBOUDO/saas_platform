<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UtilisateurAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone_whatsapp' => $this->telephone_whatsapp,
            'telephone_appel' => $this->telephone_appel,
            'statut' => $this->statut,
            'roles' => $this->roles->pluck('name')->sort()->values(),
            // `users.id` et `eleves.id` sont deux clés distinctes : un contrat se
            // rattache à l'élève, pas au compte. Sans cet objet, l'écran des
            // contrats ne peut pas construire son sélecteur.
            'eleve' => $this->whenLoaded('eleve', fn () => $this->eleve ? [
                'id' => $this->eleve->id,
                'classe' => $this->eleve->relationLoaded('classe') && $this->eleve->classe ? [
                    'id' => $this->eleve->classe->id,
                    'nom' => $this->eleve->classe->nom,
                    'sigle' => $this->eleve->classe->sigle,
                ] : null,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}