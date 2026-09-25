<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Actualité côté public. Le contenu complet n'est livré sur le détail
 * (`showContenu = true`) que pour éviter de gonfler les listes.
 */
class ActualitePublicResource extends JsonResource
{
    public bool $showContenu = false;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'titre' => $this->titre,
            'resume' => $this->resume,
            'contenu' => $this->when($this->showContenu, $this->contenu),
            'video_url' => $this->video_url,
            'lien_externe' => $this->lien_externe,
            'image_url' => $this->getFirstMediaUrl('image_principale') ?: null,
            'published_at' => $this->published_at?->toIso8601String(),
            'nb_vues' => $this->nb_vues,
            'nb_reactions' => $this->nb_reactions,
            'reaction_counts' => $this->when($this->showContenu, fn () => $this->reactionCounts()),
            'auteur' => $this->auteur ? trim(($this->auteur->prenom ?? '').' '.($this->auteur->nom ?? '')) : null,
        ];
    }

    protected function reactionCounts(): array
    {
        return $this->reactions()
            ->select('reaction', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('reaction')
            ->pluck('total', 'reaction')
            ->all();
    }
}