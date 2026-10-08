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
            // `url` : URL absolue Blade (backoffice historique).
            // `route_angular` : chemin interne de l'espace, à router côté front.
            'url' => $this->url,
            'route_angular' => $this->route_angular,
            'data' => $this->data ?? [],
            'lu' => $this->lu,
            'date_lecture' => $this->date_lecture?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}