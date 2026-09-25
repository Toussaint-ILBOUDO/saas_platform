<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProduitPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'nom' => $this->nom,
            'description' => $this->description,
            'prix' => $this->prix,
            'frais_livraison' => $this->frais_livraison,
            'categorie' => $this->categorie?->nom,
            'image_url' => $this->getFirstMediaUrl('image_principale') ?: null,
            'is_active' => $this->is_active,
        ];
    }
}