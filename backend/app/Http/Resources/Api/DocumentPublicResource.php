<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fichier = $this->getFirstMedia('document');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'titre' => $this->titre,
            'resume' => $this->resume,
            'description' => $this->description,
            'type_document' => $this->typeDocument?->nom,
            'matiere' => $this->matiere?->nom,
            'classe' => $this->classe?->nom,
            'periode' => $this->periodeRef?->nom,
            'tags' => $this->tags->pluck('nom'),
            // Collection « document » sur disque privé : aucune URL n'est
            // exposée (lecture et téléchargement passent par des routes
            // contrôlées `documents/{slug}/fichier|telecharger`).
            'types' => $this->getMedia('document')->map(fn ($media) => [
                'url' => null,
                'nom' => $media->file_name,
                'taille' => $media->size,
                'mime' => $media->mime_type,
            ]),
            'fichier' => $fichier ? [
                'nom' => $fichier->file_name,
                'taille' => $fichier->size,
                'mime' => $fichier->mime_type,
                'extension' => $fichier->extension,
            ] : null,
            'nb_vues' => $this->nb_vues,
            'nb_telechargements' => $this->nb_telechargements,
            'nb_aimes' => $this->nb_aimes ?? 0,
            'nb_n_aimes' => $this->nb_n_aimes ?? 0,
            'nombre_favoris' => $this->nombre_favoris ?? 0,
            'note_moyenne' => $this->note_moyenne,
            'nb_notes' => $this->nb_notes,
            // État de l'identité courante (membre ou visiteur de session) :
            // colonne virtuelle chargée par `withReactionForIdentity` dans les
            // listes, requête unique sinon (page de détail).
            'ma_reaction' => $this->ma_reaction,
            'is_favori' => (bool) ($this->is_favori ?? false),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
