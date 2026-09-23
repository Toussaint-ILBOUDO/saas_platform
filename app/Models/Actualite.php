<?php

namespace App\Models;

use App\Modules\Communication\Enums\ActualiteDestinataire;
use App\Modules\Communication\Enums\ActualiteStatut;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\File;

class Actualite extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    protected $table = 'actualites';

    protected $fillable = [
        'user_id',
        'titre',
        'slug',
        'resume',
        'contenu',
        'video_url',
        'lien_externe',
        'statut',
        'published_at',
        'destinataires',
        'canal_notification',
        'notification_envoyee',
        'is_active',
        'nb_vues',
        'nb_reactions',
        'nb_partages',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'destinataires' => 'array',
            'notification_envoyee' => 'boolean',
            'is_active' => 'boolean',
            'nb_vues' => 'integer',
            'nb_reactions' => 'integer',
            'nb_partages' => 'integer',
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
        return $this->hasMany(ActualiteReaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePubliee(Builder $query): Builder
    {
        return $query
            ->where('statut', ActualiteStatut::PUBLIE)
            ->where('is_active', true);
    }

    public function scopeRecherche(Builder $query, string $search): Builder
    {
        return $query
            ->where(function (Builder $q) use ($search) {
                $q->where('titre', 'ilike', "%{$search}%")
                    ->orWhere('resume', 'ilike', "%{$search}%");
            });
    }

    /**
     * Actualités internes destinées à un ou plusieurs publics (valeurs
     * métier de ActualiteDestinataire). Une actualité sans destinataires
     * (null) est considérée comme globale et visible de tous.
     */
    public function scopePourDestinataires(Builder $query, array $destinataires): Builder
    {
        return $query
            ->publiee()
            ->where(function (Builder $q) use ($destinataires) {
                $q->whereNull('destinataires');

                foreach ($destinataires as $destinataire) {
                    $q->orWhereJsonContains('destinataires', $destinataire);
                }
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(500)
            ->sharpen(10)
            ->nonQueued();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image_principale')
            ->singleFile()
            ->acceptsFile(fn (File $file) => str_starts_with($file->mimeType, 'image/'));

        $this->addMediaCollection('galerie')
            ->acceptsFile(fn (File $file) => str_starts_with($file->mimeType, 'image/'));

        $this->addMediaCollection('document')
            ->singleFile();
    }

    /*
    |--------------------------------------------------------------------------
    | Accesseurs
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('image_principale', 'thumb')
            ?: asset('assets/img/logo.png');
    }

    public function getImageOriginalAttribute(): string
    {
        return $this->getFirstMediaUrl('image_principale')
            ?: asset('assets/img/logo.png');
    }

    public function getDocumentUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('document');

        return $media ? $media->getUrl() : null;
    }

    public function getGalerieAttribute(): Collection
    {
        return $this->getMedia('galerie');
    }

    public function getVideoEmbedUrlAttribute(): ?string
    {
        if (empty($this->video_url)) {
            return null;
        }

        if (preg_match('/(?:youtu\.be\/|youtube\.com\/watch\?v=)([\w-]+)/', $this->video_url, $matches)) {
            return 'https://www.youtube.com/embed/'.$matches[1];
        }

        return $this->video_url;
    }

    public function getEstPublieeAttribute(): bool
    {
        return $this->statut === ActualiteStatut::PUBLIE;
    }

    /**
     * Badges destinataires (libellés métier) pour l'affichage en liste.
     */
    public function getDestinatairesLabelsAttribute(): array
    {
        return array_map(fn (string $value) => [
            'value' => $value,
            'label' => ActualiteDestinataire::LABELS[$value] ?? $value,
            'icon' => ActualiteDestinataire::ICONS[$value] ?? 'bi-people',
        ], $this->destinataires ?? []);
    }

    /**
     * Une actualité sans destinataires ciblés est globale (toute la communauté).
     */
    public function getEstGlobaleAttribute(): bool
    {
        return empty($this->destinataires);
    }

    /**
     * Une actualité avec « destinataires » est réservée à la communauté
     * interne (élève, enseignant, parent).
     */
    public function getEstInterneAttribute(): bool
    {
        return ! empty($this->destinataires);
    }

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::saving(function (Actualite $actualite) {
            if (empty($actualite->slug)) {
                $actualite->slug = Str::slug($actualite->titre);
            }
        });
    }
}
