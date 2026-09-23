<?php

namespace App\Modules\Temoignages\Services;

use App\Models\Temoignage;
use App\Models\TemoignageCommentaire;
use App\Models\TemoignageReaction;
use App\Models\TemoignageSignalement;
use App\Models\User;
use App\Modules\Temoignages\Enums\TemoignageReactionType as TemoignageReactionEnum;
use App\Modules\Temoignages\Enums\TemoignageStatut;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TemoignageService
{
    protected const CACHE_CLASSEMENT = 'temoignages.classement';

    protected const CACHE_REACTIONS_PREFIX = 'temoignages.reactions.';

    protected const STATUT_SIGNALEMENT_EN_ATTENTE = 'en_attente';

    protected const STATUT_SIGNALEMENT_TRAITE = 'traite';

    protected const STATUT_SIGNALEMENT_REJETE = 'rejete';

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    public function listeAdmin(string $statut = '', string $search = '', int $perPage = 15): Paginator
    {
        return Temoignage::query()
            ->with([
                'auteur:id,prenom,nom',
                'signalements',
            ])
            ->withCount([
                'reactions',
                'commentaires',
                'signalements as signalements_en_attente' => fn ($query) => $query
                    ->where('statut', self::STATUT_SIGNALEMENT_EN_ATTENTE),
            ])
            ->withScore()
            ->when($statut !== '', fn ($query) => $query->where('statut', $statut))
            ->when($search !== '', fn ($query) => $query->recherche($search))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function signalements(string $statut = '', int $perPage = 20): Paginator
    {
        return TemoignageSignalement::query()
            ->with([
                'temoignage:id,user_id,slug,contenu,statut,anonyme,published_at',
                'user:id,prenom,nom',
            ])
            ->when($statut !== '', fn ($query) => $query->where('statut', $statut))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function masquer(Temoignage $temoignage): Temoignage
    {
        $temoignage->update([
            'statut' => TemoignageStatut::MASQUE,
        ]);

        $this->flushCache();

        return $temoignage->fresh();
    }

    public function restaurer(Temoignage $temoignage): Temoignage
    {
        $temoignage->update([
            'statut' => TemoignageStatut::PUBLIE,
            'published_at' => now(),
        ]);

        $this->flushCache();

        return $temoignage->fresh();
    }

    public function delete(Temoignage $temoignage): void
    {
        $temoignage->delete();

        $this->flushCache();
    }

    public function traiterSignalement(TemoignageSignalement $signalement, string $statut, bool $masquer = false): TemoignageSignalement
    {
        $signalement->update([
            'statut' => $statut,
        ]);

        if ($masquer && $signalement->temoignage && $signalement->temoignage->est_publie) {
            $this->masquer($signalement->temoignage);
        }

        return $signalement->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Espace privé
    |--------------------------------------------------------------------------
    */

    public function listeMes(int $userId, int $perPage = 15): Paginator
    {
        return Temoignage::query()
            ->withCount('reactions')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data, User $user): Temoignage
    {
        $temoignage = Temoignage::create([
            'user_id' => $user->id,
            'slug' => $this->uniqueSlug($data['contenu']),
            'contenu' => $data['contenu'],
            'role' => $user->getRoleNames()->first(),
            'anonyme' => (bool) ($data['anonyme'] ?? false),
            'statut' => TemoignageStatut::PUBLIE,
            'published_at' => now(),
        ]);

        $this->flushCache();

        return $temoignage;
    }

    public function update(Temoignage $temoignage, array $data): Temoignage
    {
        $temoignage->update([
            'contenu' => $data['contenu'],
            'anonyme' => (bool) ($data['anonyme'] ?? false),
        ]);

        $this->flushCache();

        return $temoignage->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Public — classement R1/B1/R2/B2…
    |--------------------------------------------------------------------------
    */

    public function classement(): Collection
    {
        return Cache::remember(self::CACHE_CLASSEMENT, 3600, function () {
            $base = fn () => Temoignage::query()
                ->publie()
                ->with('auteur:id,prenom,nom')
                ->withScore();

            $recents = $base()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->get();

            $meilleurs = $base()
                ->orderByDesc('score')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->get();

            $merged = collect();
            $dejaVus = [];
            $max = max($recents->count(), $meilleurs->count());

            for ($i = 0; $i < $max; $i++) {
                foreach ([$recents, $meilleurs] as $liste) {
                    $item = $liste->get($i);

                    if ($item && ! in_array($item->id, $dejaVus, true)) {
                        $dejaVus[] = $item->id;
                        $merged->push($item);
                    }
                }
            }

            return $merged;
        });
    }

    public function listeClassement(int $perPage = 6): LengthAwarePaginatorContract
    {
        $merged = $this->classement();

        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $items = $merged->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($items, $merged->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }

    public function topHome(int $limit = 3): Collection
    {
        return $this->classement()->take($limit)->values();
    }

    public function autres(int $limit = 4, ?int $excludeId = null): Collection
    {
        return $this->classement()
            ->reject(fn (Temoignage $temoignage) => $temoignage->id === $excludeId)
            ->take($limit)
            ->values();
    }

    public function getBySlug(string $slug): Temoignage
    {
        return Temoignage::query()
            ->publie()
            ->with('auteur:id,prenom,nom')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Réactions
    |--------------------------------------------------------------------------
    */

    public function toggleReaction(
        Temoignage $temoignage,
        string $reaction,
        string $ip,
        ?int $userId = null
    ): array {
        return DB::transaction(function () use ($temoignage, $reaction, $ip, $userId) {
            $existing = TemoignageReaction::query()
                ->where('temoignage_id', $temoignage->id)
                ->where('ip', $ip)
                ->first();

            $current = null;

            if ($existing) {
                if ($existing->reaction === $reaction) {
                    $existing->delete();
                    $temoignage->decrement('nb_reactions');
                } else {
                    $existing->update([
                        'reaction' => $reaction,
                        'user_id' => $userId,
                    ]);
                    $current = $reaction;
                }
            } else {
                TemoignageReaction::create([
                    'temoignage_id' => $temoignage->id,
                    'user_id' => $userId,
                    'reaction' => $reaction,
                    'ip' => $ip,
                ]);
                $temoignage->increment('nb_reactions');
                $current = $reaction;
            }

            $this->forgetReactionCounts($temoignage);
            $this->flushCache();

            $fresh = $temoignage->fresh();
            $fresh->setAttribute('score', $this->scoreOf($fresh));

            return [
                'current' => $current,
                'total' => $fresh->nb_reactions,
                'counts' => $this->reactionCounts($temoignage),
                'score' => $fresh->score,
            ];
        });
    }

    public function reactionCounts(Temoignage $temoignage): Collection
    {
        return Cache::remember(
            self::CACHE_REACTIONS_PREFIX.$temoignage->id,
            300,
            fn () => $temoignage->reactions()
                ->select('reaction', DB::raw('count(*) as total'))
                ->groupBy('reaction')
                ->pluck('total', 'reaction')
        );
    }

    public function currentReaction(Temoignage $temoignage, string $ip): ?string
    {
        return TemoignageReaction::query()
            ->where('temoignage_id', $temoignage->id)
            ->where('ip', $ip)
            ->value('reaction');
    }

    public function scoreOf(Temoignage $temoignage): int
    {
        return (int) $temoignage->reactions()
            ->select(DB::raw(TemoignageReactionEnum::scoreSql().' as total'))
            ->value('total');
    }

    /*
    |--------------------------------------------------------------------------
    | Commentaires & signalements
    |--------------------------------------------------------------------------
    */

    public function commenter(
        Temoignage $temoignage,
        User $user,
        string $contenu,
        ?int $parentId = null
    ): TemoignageCommentaire {
        $commentaire = TemoignageCommentaire::create([
            'temoignage_id' => $temoignage->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'contenu' => $contenu,
        ]);

        $temoignage->increment('nb_commentaires');

        $this->flushCache();

        return $commentaire;
    }

    public function signale(
        Temoignage $temoignage,
        User $user,
        string $motif,
        ?string $description = null
    ): TemoignageSignalement {
        $existant = TemoignageSignalement::query()
            ->where('temoignage_id', $temoignage->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_if($existant, 409, 'Vous avez déjà signalé ce témoignage.');

        return TemoignageSignalement::create([
            'temoignage_id' => $temoignage->id,
            'user_id' => $user->id,
            'motif' => $motif,
            'description' => $description,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function uniqueSlug(string $contenu): string
    {
        $slug = Str::slug(Str::limit(strip_tags($contenu), 60));
        $original = $slug;
        $suffix = 2;

        while (Temoignage::withTrashed()
            ->where('slug', $slug)
            ->exists()) {
            $slug = $original.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function flushCache(): void
    {
        Cache::forget(self::CACHE_CLASSEMENT);
    }

    protected function forgetReactionCounts(Temoignage $temoignage): void
    {
        Cache::forget(self::CACHE_REACTIONS_PREFIX.$temoignage->id);
    }
}
