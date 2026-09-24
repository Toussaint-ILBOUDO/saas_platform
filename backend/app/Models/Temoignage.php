<?php

namespace App\Models;

use App\Modules\Temoignages\Enums\TemoignageReactionType;
use App\Modules\Temoignages\Enums\TemoignageStatut;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Temoignage extends Model
{
    use SoftDeletes;

    protected $table = 'temoignages';

    protected $fillable = [
        'user_id',
        'slug',
        'contenu',
        'role',
        'anonyme',
        'statut',
        'published_at',
        'is_active',
        'nb_reactions',
        'nb_commentaires',
    ];

    protected function casts(): array
    {
        return [
            'anonyme' => 'boolean',
            'statut' => 'string',
            'published_at' => 'datetime',
            'is_active' => 'boolean',
            'nb_reactions' => 'integer',
            'nb_commentaires' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(TemoignageReaction::class);
    }

    public function commentaires(): HasMany
    {
        return $this->hasMany(TemoignageCommentaire::class);
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(TemoignageSignalement::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePublie(Builder $query): Builder
    {
        return $query
            ->where('statut', TemoignageStatut::PUBLIE)
            ->where('is_active', true);
    }

    public function scopeMasque(Builder $query): Builder
    {
        return $query->where('statut', TemoignageStatut::MASQUE);
    }

    public function scopeWithScore(Builder $query): Builder
    {
        return $query->withCount([
            'reactions as score' => fn (Builder $q) => $q->select(DB::raw(TemoignageReactionType::scoreSql())),
        ]);
    }

    public function scopeRecherche(Builder $query, string $search): Builder
    {
        return $query->where('contenu', 'ilike', "%{$search}%");
    }

    /*
    |--------------------------------------------------------------------------
    | Accesseurs
    |--------------------------------------------------------------------------
    */

    public function getScoreAttribute(): int
    {
        return (int) ($this->attributes['score'] ?? 0);
    }

    public function getAuteurDisplayAttribute(): string
    {
        if ($this->anonyme) {
            return 'Témoignage anonyme';
        }

        if (! $this->relationLoaded('auteur') || ! $this->auteur) {
            return 'Témoignage anonyme';
        }

        $prenom = $this->auteur->prenom ?? '';
        $initial = mb_strtoupper(mb_substr((string) $this->auteur->nom, 0, 1));

        return trim($prenom.' '.$initial.'.');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'parent' => 'Parent d\'élève',
            'enseignant' => 'Enseignant',
            'eleve' => 'Élève',
            default => ucfirst((string) $this->role),
        };
    }

    public function getEstPublieAttribute(): bool
    {
        return $this->statut === TemoignageStatut::PUBLIE;
    }

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::saving(function (Temoignage $temoignage) {
            if (empty($temoignage->slug)) {
                $temoignage->slug = Str::slug(Str::limit($temoignage->contenu, 60));
            }
        });
    }
}
