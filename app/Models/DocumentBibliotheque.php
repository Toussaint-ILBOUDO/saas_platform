<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DocumentBibliotheque extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'document_bibliotheques';

    protected $fillable = [
        'user_id',
        'titre',
        'description',
        'resume',
        'type_document_id',
        'classe_id',
        'matiere_id',
        'periode_id',
        'is_public',
        'statut',
        'slug',
        'nb_vues',
        'nb_telechargements',
        'nombre_favoris',
        'note_moyenne',
        'nb_notes',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'nb_vues' => 'integer',
        'nb_telechargements' => 'integer',
        'nombre_favoris' => 'integer',
        'note_moyenne' => 'float',
        'nb_notes' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DocumentBibliotheque $document) {
            if (empty($document->slug)) {
                $document->slug = $document->generateUniqueSlug($document->titre);
            }
        });

        static::updating(function (DocumentBibliotheque $document) {
            if ($document->isDirty('titre') && empty($document->slug)) {
                $document->slug = $document->generateUniqueSlug($document->titre);
            }
        });
    }

    public function generateUniqueSlug(string $titre): string
    {
        $slug = Str::slug($titre);
        $original = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | Médias — stockage privé
    |--------------------------------------------------------------------------
    | La collection « document » est stockée sur un disque privé
    | (storage/app/private/media) : aucun fichier n'est exposé via une URL
    | publique directe. L'accès passe par les routes contrôlées
    | (bibliothequepub.view / bibliothequepub.download) qui appliquent la
    | DocumentBibliothequePolicy.
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')
            ->singleFile()
            ->useDisk('private_media');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function typeDocument(): BelongsTo
    {
        return $this->belongsTo(TypeDocument::class, 'type_document_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

    public function periodeRef(): BelongsTo
    {
        return $this->belongsTo(PeriodeDocument::class, 'periode_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tags')
            ->withTimestamps();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(DocumentNote::class, 'document_bibliotheque_id');
    }

    public function commentaires(): HasMany
    {
        return $this->hasMany(DocumentCommentaire::class, 'document_bibliotheque_id');
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(DocumentSignalement::class, 'document_bibliotheque_id');
    }

    public function favoris(): HasMany
    {
        return $this->hasMany(FavoriBibliotheque::class, 'document_bibliotheque_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(DocumentAccessLog::class, 'document_bibliotheque_id');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true)->where('statut', 'publie');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeStatut(Builder $query, string $statut): Builder
    {
        return $query->where('statut', $statut);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function ($q) use ($search) {
            $q->where(function ($q2) use ($search) {
                $q2->where('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('resume', 'like', "%{$search}%");
            });
        });
    }

    public function getFichierUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('document');
        return $media && $media->disk === 'public' ? $media->getUrl() : null;
    }

    public function getFichierPathAttribute(): ?string
    {
        $media = $this->getFirstMedia('document');
        return $media ? $media->getPath() : null;
    }

    public function getIsFavoriAttribute(): bool
    {
        if (array_key_exists('is_favori', $this->attributes)) {
            return (bool) $this->attributes['is_favori'];
        }

        if (!auth()->check()) {
            return false;
        }

        return $this->favoris()->where('user_id', auth()->id())->exists();
    }

    public function scopeWithFavoriForUser(Builder $query, ?int $userId): Builder
    {
        $query->addSelect('document_bibliotheques.*');

        if (!$userId) {
            return $query->selectRaw('false as is_favori');
        }

        return $query->selectRaw(
            'EXISTS (SELECT 1 FROM favori_bibliotheques WHERE favori_bibliotheques.document_bibliotheque_id = document_bibliotheques.id AND favori_bibliotheques.user_id = ?) as is_favori',
            [$userId]
        );
    }
}
