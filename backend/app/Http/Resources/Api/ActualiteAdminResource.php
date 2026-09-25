<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Actualité côté backoffice (T3.5). Contenu complet toujours visible :
 * réservé au staff (`/api/admin`).
 */
class ActualiteAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'slug' => $this->slug,
            'resume' => $this->resume,
            'contenu' => $this->contenu,
            'video_url' => $this->video_url,
            'lien_externe' => $this->lien_externe,
            'image_url' => $this->getFirstMediaUrl('image_principale') ?: null,
            'document_url' => $this->document_url,
            'is_active' => $this->is_active,
            'statut' => $this->statut,
            'est_publiee' => $this->est_publiee,
            'destinataires' => $this->destinataires ?? [],
            'notification_envoyee' => $this->notification_envoyee,
            'published_at' => $this->published_at?->toIso8601String(),
            'nb_vues' => $this->nb_vues,
            'nb_reactions' => $this->nb_reactions,
            'nb_partages' => $this->nb_partages,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'auteur' => $this->auteur ? [
                'id' => $this->auteur->id,
                'prenom' => $this->auteur->prenom,
                'nom' => $this->auteur->nom,
                'email' => $this->auteur->email,
            ] : null,
        ];
    }
}