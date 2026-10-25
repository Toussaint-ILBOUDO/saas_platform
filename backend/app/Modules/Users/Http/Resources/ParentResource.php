<?php

namespace App\Modules\Users\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 *
 * Parent du cabinet : le `User` porteur du rôle « parent » + son profil.
 */
class ParentResource extends JsonResource
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
            'statut' => (bool) $this->statut,
            'profil' => $this->whenLoaded('parentProfil', fn () => $this->parentProfil ? [
                'id' => $this->parentProfil->id,
                'profession' => $this->parentProfil->profession,
                'adresse_domicile' => $this->parentProfil->adresse_domicile,
                'nombre_enfants' => (int) $this->parentProfil->nombre_enfants,
            ] : null),
            'nombre_enfants' => $this->whenLoaded(
                'enfants',
                fn () => $this->enfants->count()
            ),
            // Les enfants rattachés (M3 : le volet détail de l'écran Scolarité).
            // `whenLoaded` : la liste générique charge elle aussi `enfants` —
            // chaque ligne expose alors sa famille, c'est voulu (le sélecteur
            // de parent des contrats s'en sert) à condition de charger
            // `enfants.user` (cf. `ParentApiController::index`).
            'enfants' => $this->whenLoaded(
                'enfants',
                fn () => EleveResource::collection($this->enfants)
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
