<?php

namespace App\Modules\Users\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Eleve
 *
 * Élève du cabinet : le profil `eleves` (clé attendue par les contrats,
 * `eleves.id`) enrichi des informations de compte et de rattachement.
 */
class EleveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'nom' => $user?->nom,
            'prenom' => $user?->prenom,
            'email' => $user?->email,
            'telephone_whatsapp' => $user?->telephone_whatsapp,
            'telephone_appel' => $user?->telephone_appel,
            // `eleves.statut` pilote l'activation du compte (voir AuthService) :
            // false = le compte élève existe mais ne peut pas encore se connecter.
            'statut_compte' => (bool) $this->statut,
            'ecole' => $this->ecole,
            'date_naissance' => $this->date_naissance,
            'lieu_naissance' => $this->lieu_naissance,
            'classe' => $this->whenLoaded('classe', fn () => $this->classe ? [
                'id' => $this->classe->id,
                'nom' => $this->classe->nom,
                'sigle' => $this->classe->sigle,
            ] : null),
            'parent' => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id' => $this->parent->id,
                'nom' => $this->parent->nom,
                'prenom' => $this->parent->prenom,
                'telephone_whatsapp' => $this->parent->telephone_whatsapp,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
