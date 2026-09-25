<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'titre' => $this->titre,
            'resume' => $this->resume,
            'types' => $this->getMedia('fichier')->map(fn ($media) => [
                'url' => $media->getUrl(),
                'nom' => $media->file_name,
                'taille' => $media->size,
            ]),
            'matiere' => $this->matiere?->nom,
            'classe' => $this->classe?->nom,
            'nb_vues' => $this->nb_vues,
            'nb_telechargements' => $this->nb_telechargements,
            'note_moyenne' => $this->note_moyenne,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}