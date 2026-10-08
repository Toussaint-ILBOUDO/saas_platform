<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Utilisateur courant (session web) : identité, rôles et rôle actif.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'statut' => $this->statut ?? true,
            'roles' => $this->getRoleNames()->sort()->values(),
            'active_role' => session('active_role'),
            'photo_url' => $this->photo_profil_url,

            // `eleves.id` n'est pas `users.id`. L'élève en a besoin pour ouvrir
            // « son » historique pédagogique (`/api/mes-enfants/{eleve}/…`) :
            // sans cet identifiant, le client ne peut pas construire l'URL et
            // l'écran de consultation lui reste inaccessible.
            'eleve' => $this->whenLoaded('eleve', fn () => $this->eleve ? [
                'id' => $this->eleve->id,
            ] : null),
        ];
    }
}