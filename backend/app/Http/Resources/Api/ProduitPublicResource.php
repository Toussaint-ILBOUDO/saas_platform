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
            'reference' => $this->reference,
            'nom' => $this->nom,
            'description' => $this->description,
            'prix' => (float) $this->prix,
            'prix_promo' => $this->prix_promo !== null ? (float) $this->prix_promo : null,
            'prix_vente' => $this->prixVente,
            'en_promotion' => $this->enPromotion,
            'frais_livraison' => (float) $this->frais_livraison,
            'categorie' => $this->categorie?->nom,
            'categorie_id' => $this->categorie_id,
            // Route contrôlée : l'URL /storage ne résout pas en multi-tenant
            // (disque public réécrit vers storage/cabinet<id>/app/public/).
            'image_url' => $this->imageUrl(),
            // Stock : `null` = produit non suivi (toujours disponible).
            'stock' => $this->stock,
            'disponible' => $this->estDisponible(),
            'is_active' => $this->is_active,
        ];
    }
}
