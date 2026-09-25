<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'url' => $this->url,
            'data' => $this->data ?? [],
            'lu' => $this->lu,
            'date_lecture' => $this->date_lecture?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}