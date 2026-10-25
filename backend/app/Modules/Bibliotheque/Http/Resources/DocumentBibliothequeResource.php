<?php

namespace App\Modules\Bibliotheque\Http\Resources;

use App\Models\DocumentBibliotheque;
use App\Models\DocumentCommentaire;
use App\Models\DocumentSignalement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\DocumentBibliotheque
 *
 * Document de la bibliothèque — forme unique pour l'espace Angular (membre
 * comme modération). Le fichier est stocké sur un disque privé : la ressource
 * n'expose jamais d'URL directe, seulement les métadonnées. L'accès au binaire
 * passe par les routes contrôlées `bibliotheque/fichier` et
 * `bibliotheque/telecharger`, qui appliquent la policy et comptent l'accès.
 */
class DocumentBibliothequeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var DocumentBibliotheque $document */
        $document = $this->resource;
        $media = $document->getFirstMedia('document');

        return [
            'id' => $document->id,
            'slug' => $document->slug,
            'titre' => $document->titre,
            'description' => $document->description,
            'resume' => $document->resume,
            'statut' => $document->statut,
            'is_public' => (bool) $document->is_public,
            'nb_vues' => (int) $document->nb_vues,
            'nb_telechargements' => (int) $document->nb_telechargements,
            'nombre_favoris' => (int) $document->nombre_favoris,
            'note_moyenne' => (float) $document->note_moyenne,
            'nb_notes' => (int) $document->nb_notes,
            'is_favori' => (bool) $document->is_favori,
            'ma_note' => $document->relationLoaded('notes')
                ? $document->notes->firstWhere('user_id', auth()->id())?->note
                : null,
            'auteur' => $document->relationLoaded('auteur') && $document->auteur ? [
                'id' => $document->auteur->id,
                'nom' => $document->auteur->nom,
                'prenom' => $document->auteur->prenom,
            ] : null,
            'type_document' => $document->relationLoaded('typeDocument') && $document->typeDocument ? [
                'id' => $document->typeDocument->id,
                'nom' => $document->typeDocument->nom,
                'sigle' => $document->typeDocument->sigle,
            ] : null,
            'classe' => $document->relationLoaded('classe') && $document->classe ? [
                'id' => $document->classe->id,
                'nom' => $document->classe->nom,
                'sigle' => $document->classe->sigle,
            ] : null,
            'matiere' => $document->relationLoaded('matiere') && $document->matiere ? [
                'id' => $document->matiere->id,
                'nom' => $document->matiere->nom,
                'sigle' => $document->matiere->sigle,
            ] : null,
            'periode' => $document->relationLoaded('periodeRef') && $document->periodeRef ? [
                'id' => $document->periodeRef->id,
                'nom' => $document->periodeRef->nom,
                'sigle' => $document->periodeRef->sigle,
            ] : null,
            'tags' => $document->relationLoaded('tags')
                ? $document->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'nom' => $tag->nom,
                    'slug' => $tag->slug,
                ])->values()
                : [],
            'fichier' => $media ? [
                'nom' => $media->file_name,
                'taille' => (int) $media->size,
                'mime' => $media->mime_type,
                'extension' => $media->extension,
            ] : null,
            // Seuls les commentaires « racines » figurent dans la liste ; les
            // réponses sont imbriquées sous leur parent.
            'commentaires' => $document->relationLoaded('commentaires')
                ? $document->commentaires
                    ->whereNull('parent_id')
                    ->sortByDesc('created_at')
                    ->map(fn (DocumentCommentaire $c) => $this->commentaire($c))
                    ->values()
                : null,
            'signalements' => $document->relationLoaded('signalements')
                ? $document->signalements->map(fn (DocumentSignalement $s) => [
                    'id' => $s->id,
                    'motif' => $s->motif,
                    'motif_libelle' => DocumentSignalement::libelleMotif($s->motif),
                    'description' => $s->description,
                    'precisions' => $s->precisions,
                    'statut' => $s->statut,
                    'statut_libelle' => DocumentSignalement::libelleStatut($s->statut),
                    'note_admin' => $s->note_admin,
                    'user' => $s->relationLoaded('user') && $s->user ? [
                        'id' => $s->user->id,
                        'nom' => $s->user->nom,
                        'prenom' => $s->user->prenom,
                    ] : null,
                    // Trace de l'action de modération effectuée sur ce signalement.
                    'traite_at' => $s->traite_at?->toIso8601String(),
                    'traite_par' => $s->relationLoaded('traitePar') && $s->traitePar ? [
                        'id' => $s->traitePar->id,
                        'nom' => $s->traitePar->nom,
                        'prenom' => $s->traitePar->prenom,
                    ] : null,
                    'created_at' => $s->created_at?->toIso8601String(),
                ])->values()
                : null,
            'created_at' => $document->created_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String(),
        ];
    }

    private function commentaire(DocumentCommentaire $c): array
    {
        return [
            'id' => $c->id,
            'contenu' => $c->contenu,
            'signale' => (bool) $c->signale,
            'user' => $c->relationLoaded('user') && $c->user ? [
                'id' => $c->user->id,
                'nom' => $c->user->nom,
                'prenom' => $c->user->prenom,
            ] : null,
            'replies' => $c->relationLoaded('replies')
                ? $c->replies->sortBy('created_at')->map(fn ($r) => $this->commentaire($r))->values()
                : [],
            'created_at' => $c->created_at?->toIso8601String(),
        ];
    }
}
