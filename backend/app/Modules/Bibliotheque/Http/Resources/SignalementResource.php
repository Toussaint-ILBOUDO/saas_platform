<?php

namespace App\Modules\Bibliotheque\Http\Resources;

use App\Models\DocumentSignalement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\DocumentSignalement
 *
 * Signalement d'un document — vu depuis la modération : le document signalé
 * (avec son auteur), l'auteur du signalement (ou anonyme) et la trace de
 * traitement.
 */
class SignalementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'motif' => $this->motif,
            'motif_libelle' => DocumentSignalement::libelleMotif($this->motif),
            'description' => $this->description,
            'precisions' => $this->precisions,
            'statut' => $this->statut,
            'statut_libelle' => DocumentSignalement::libelleStatut($this->statut),
            'note_admin' => $this->note_admin,
            'email_contact' => $this->email_contact,
            'anonyme' => $this->user_id === null,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'nom' => $this->user->nom,
                'prenom' => $this->user->prenom,
            ] : null),
            'traite_par' => $this->whenLoaded('traitePar', fn () => $this->traitePar ? [
                'id' => $this->traitePar->id,
                'nom' => $this->traitePar->nom,
                'prenom' => $this->traitePar->prenom,
            ] : null),
            'traite_at' => $this->traite_at?->toIso8601String(),
            'document' => $this->whenLoaded('document', fn () => $this->document ? [
                'id' => $this->document->id,
                'titre' => $this->document->titre,
                'slug' => $this->document->slug,
                'statut' => $this->document->statut,
                'is_public' => (bool) $this->document->is_public,
                'auteur' => $this->document->relationLoaded('auteur') && $this->document->auteur ? [
                    'id' => $this->document->auteur->id,
                    'nom' => $this->document->auteur->nom,
                    'prenom' => $this->document->auteur->prenom,
                ] : null,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
