<?php

namespace App\Modules\Communication\Services;

use App\Models\Actualite;
use App\Models\ActualiteReaction;
use App\Modules\Communication\Enums\ActualiteStatut;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActualiteService
{
    protected const CACHE_RECENTES = 'actualites.recentes';

    protected const CACHE_REACTIONS_PREFIX = 'actualites.reactions.';

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    public function listAdmin(string $statut = '', string $search = '', int $perPage = 15): Paginator
    {
        return Actualite::query()
            ->with([
                'auteur:id,prenom,nom',
                'media',
            ])
            ->when($statut !== '', fn ($query) => $query->where('statut', $statut))
            ->when($search !== '', fn ($query) => $query->recherche($search))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(
        array $data,
        int $userId,
        ?UploadedFile $image = null,
        array $galerie = [],
        ?UploadedFile $document = null
    ): Actualite {
        return DB::transaction(function () use ($data, $userId, $image, $galerie, $document) {
            $statut = $data['statut'] ?? ActualiteStatut::BROUILLON;
            $publiee = $statut === ActualiteStatut::PUBLIE;

            $actualite = Actualite::create([
                'user_id' => $userId,
                'titre' => $data['titre'],
                'slug' => $this->uniqueSlug(
                    ! empty($data['slug']) ? $data['slug'] : $data['titre']
                ),
                'resume' => $data['resume'] ?? null,
                'contenu' => $data['contenu'],
                'video_url' => $data['video_url'] ?? null,
                'lien_externe' => $data['lien_externe'] ?? null,
                'statut' => $statut,
                'published_at' => $publiee ? now() : null,
                'is_active' => $publiee ? true : ($data['is_active'] ?? true),
            ]);

            $this->attachMedias($actualite, $image, $galerie, $document);

            $this->flushCache();

            return $actualite;
        });
    }

    public function update(
        Actualite $actualite,
        array $data,
        ?UploadedFile $image = null,
        array $galerie = [],
        ?UploadedFile $document = null
    ): Actualite {
        return DB::transaction(function () use ($actualite, $data, $image, $galerie, $document) {
            $statut = $data['statut'] ?? $actualite->statut;
            $publiee = $statut === ActualiteStatut::PUBLIE;

            $actualite->update([
                'titre' => $data['titre'],
                'slug' => $this->uniqueSlug(
                    ! empty($data['slug']) ? $data['slug'] : $data['titre'],
                    $actualite->id
                ),
                'resume' => $data['resume'] ?? null,
                'contenu' => $data['contenu'],
                'video_url' => $data['video_url'] ?? null,
                'lien_externe' => $data['lien_externe'] ?? null,
                'statut' => $statut,
                'published_at' => $publiee
                    ? ($actualite->published_at ?? now())
                    : $actualite->published_at,
                'is_active' => $publiee ? true : ($data['is_active'] ?? $actualite->is_active),
            ]);

            $this->attachMedias($actualite, $image, $galerie, $document);

            $this->flushCache();

            return $actualite->fresh();
        });
    }

    public function delete(Actualite $actualite): bool
    {
        return DB::transaction(function () use ($actualite) {
            $actualite->clearMediaCollection('image_principale');
            $actualite->clearMediaCollection('galerie');
            $actualite->clearMediaCollection('document');

            $deleted = $actualite->delete();

            $this->flushCache();
            $this->forgetReactionCounts($actualite);

            return $deleted;
        });
    }

    public function toggleActive(Actualite $actualite): Actualite
    {
        $actualite->update([
            'is_active' => ! $actualite->is_active,
        ]);

        $this->flushCache();

        return $actualite->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Publication
    |--------------------------------------------------------------------------
    */

    public function publish(Actualite $actualite, array $destinataires, string $canal): Actualite
    {
        $actualite->update([
            'statut' => ActualiteStatut::PUBLIE,
            'published_at' => $actualite->published_at ?? now(),
            'destinataires' => $destinataires,
            'canal_notification' => $canal,
            'notification_envoyee' => true,
            'is_active' => true,
        ]);

        $this->flushCache();

        return $actualite->fresh();
    }

    public function unpublish(Actualite $actualite): Actualite
    {
        $actualite->update([
            'statut' => ActualiteStatut::BROUILLON,
        ]);

        $this->flushCache();

        return $actualite->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */

    public function listPublic(int $perPage = 9): Paginator
    {
        return Actualite::query()
            ->publiee()
            ->with('media')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getBySlug(string $slug): Actualite
    {
        return Actualite::query()
            ->publiee()
            ->with([
                'auteur:id,prenom,nom',
                'media',
            ])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Espace interne (panel) — élève / enseignant / parent
    |--------------------------------------------------------------------------
    */

    public function listInternes(array $destinataires, int $perPage = 9): Paginator
    {
        return Actualite::query()
            ->with('media')
            ->pourDestinataires($destinataires)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getInterneBySlug(string $slug, array $destinataires): Actualite
    {
        return Actualite::query()
            ->pourDestinataires($destinataires)
            ->with([
                'auteur:id,prenom,nom',
                'media',
            ])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function recentes(int $limit = 4): Collection
    {
        return Cache::remember(
            self::CACHE_RECENTES . '.' . $limit,
            3600,
            function () use ($limit) {
                return Actualite::query()
                    ->publiee()
                    ->with('media')
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->take($limit)
                    ->get();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Statistiques
    |--------------------------------------------------------------------------
    */

    public function recordView(Actualite $actualite, Request $request): void
    {
        $key = 'actualite_vue_'.$actualite->id;

        if ($request->session()->has($key)) {
            return;
        }

        $actualite->increment('nb_vues');

        $request->session()->put($key, true);
    }

    public function recordShare(Actualite $actualite): void
    {
        $actualite->increment('nb_partages');

        $this->flushCache();
    }

    /*
    |--------------------------------------------------------------------------
    | Réactions
    |--------------------------------------------------------------------------
    */

    public function toggleReaction(
        Actualite $actualite,
        string $reaction,
        string $ip,
        ?int $userId = null
    ): array {
        return DB::transaction(function () use ($actualite, $reaction, $ip, $userId) {
            $existing = ActualiteReaction::query()
                ->where('actualite_id', $actualite->id)
                ->where('ip', $ip)
                ->first();

            $current = null;

            if ($existing) {
                if ($existing->reaction === $reaction) {
                    $existing->delete();
                    $actualite->decrement('nb_reactions');
                } else {
                    $existing->update([
                        'reaction' => $reaction,
                        'user_id' => $userId,
                    ]);
                    $current = $reaction;
                }
            } else {
                ActualiteReaction::create([
                    'actualite_id' => $actualite->id,
                    'user_id' => $userId,
                    'reaction' => $reaction,
                    'ip' => $ip,
                ]);
                $actualite->increment('nb_reactions');
                $current = $reaction;
            }

            $this->forgetReactionCounts($actualite);
            $this->flushCache();

            return [
                'current' => $current,
                'total' => $actualite->fresh()->nb_reactions,
                'counts' => $this->reactionCounts($actualite),
            ];
        });
    }

    public function reactionCounts(Actualite $actualite): Collection
    {
        return Cache::remember(
            self::CACHE_REACTIONS_PREFIX.$actualite->id,
            300,
            fn () => $actualite->reactions()
                ->select('reaction', DB::raw('count(*) as total'))
                ->groupBy('reaction')
                ->pluck('total', 'reaction')
        );
    }

    public function currentReaction(Actualite $actualite, string $ip): ?string
    {
        return ActualiteReaction::query()
            ->where('actualite_id', $actualite->id)
            ->where('ip', $ip)
            ->value('reaction');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function attachMedias(
        Actualite $actualite,
        ?UploadedFile $image,
        array $galerie,
        ?UploadedFile $document
    ): void {
        if ($image) {
            $actualite->clearMediaCollection('image_principale');
            $actualite->addMedia($image)->toMediaCollection('image_principale');
        }

        if (! empty($galerie)) {
            $actualite->clearMediaCollection('galerie');

            foreach ($galerie as $file) {
                $actualite->addMedia($file)->toMediaCollection('galerie');
            }
        }

        if ($document) {
            $actualite->clearMediaCollection('document');
            $actualite->addMedia($document)
                ->usingFileName($document->getClientOriginalName())
                ->toMediaCollection('document');
        }
    }

    protected function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $suffix = 2;

        while (Actualite::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $original.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function flushCache(): void
    {
        Cache::forget(self::CACHE_RECENTES);
    }

    protected function forgetReactionCounts(Actualite $actualite): void
    {
        Cache::forget(self::CACHE_REACTIONS_PREFIX.$actualite->id);
    }
}
