<?php

namespace App\Http\Resources;

use App\Modules\Auth\Http\Controllers\Api\ProfilApiController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Utilisateur courant (session web) : identité, rôles, rôle actif et
 * photo. `photo_url` est null tant qu'aucune photo n'a été chargée —
 * l'initiation de l'avatar (sigle) est alors faite côté client.
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
            'telephone_whatsapp' => $this->telephone_whatsapp,
            'telephone_appel' => $this->telephone_appel,
            'statut' => $this->statut ?? true,
            'roles' => $this->getRoleNames()->sort()->values(),
            'active_role' => session('active_role'),
            'photo_url' => $this->getFirstMedia('photo_profil')
                ? ProfilApiController::URL_PHOTO.'?v='.($this->getFirstMedia('photo_profil')?->updated_at?->timestamp ?? '')
                : null,

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
