<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Notification
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'type' => $this->type,
            'icone' => $this->iconeHtml,
            'couleur' => $this->couleur,
            'action_label' => $this->action_label,
            // Uniquement `route_angular` (chemin relatif de l'espace Angular) :
            // c'est le seul lien de notification qui reste (T7A.11 a retiré
            // l'accesseur `url` et les routes Blade du web KEduc). On ne
            // l'expose pas comme URL absolue : le front préfixe lui-même le
            // chemin par le domaine du cabinet.
            'route_angular' => $this->route_angular,
            'data' => $this->data ?? [],
            'lu' => $this->lu,
            'date_lecture' => $this->date_lecture?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}