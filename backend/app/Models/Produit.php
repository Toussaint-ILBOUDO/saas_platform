<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Produit extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'produits';

    protected $fillable = [
        'categorie_id',
        'nom',
        'slug',
        'reference',
        'description',
        'prix',
        'prix_promo',
        'frais_livraison',
        'stock',
        'is_active',
        'est_archive',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'prix_promo' => 'decimal:2',
        'frais_livraison' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'est_archive' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Produit $produit) {
            if (empty($produit->slug)) {
                $produit->slug = $produit->generateUniqueSlug($produit->nom);
            }
        });

        static::updating(function (Produit $produit) {
            if ($produit->isDirty('nom') && empty($produit->slug)) {
                $produit->slug = $produit->generateUniqueSlug($produit->nom);
            }
        });
    }

    public function generateUniqueSlug(string $nom): string
    {
        $slug = Str::slug($nom);
        $original = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // ======================
    // RELATIONS
    // ======================

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieProduit::class, 'categorie_id');
    }

    public function lignesCommande(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'produit_id');
    }

    // ======================
    // SCOPES
    // ======================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('est_archive', false);
    }

    /**
     * Prix effectivement facturé : le prix promotionnel s'il est renseigné et
     * inférieur au prix normal, sinon le prix courant. Source unique de vérité
     * côté serveur (panier et commande recalculent toujours à partir d'ici).
     */
    public function getPrixVenteAttribute(): float
    {
        $prix = (float) $this->prix;

        if ($this->prix_promo !== null && (float) $this->prix_promo > 0 && (float) $this->prix_promo < $prix) {
            return (float) $this->prix_promo;
        }

        return $prix;
    }

    public function getEnPromotionAttribute(): bool
    {
        return $this->prix_promo !== null
            && (float) $this->prix_promo > 0
            && (float) $this->prix_promo < (float) $this->prix;
    }

    /** Stock géré (null = produit non suivi, toujours disponible). */
    public function estDisponible(int $quantite = 1): bool
    {
        if ($this->est_archive || ! $this->is_active) {
            return false;
        }

        if ($this->stock === null) {
            return true;
        }

        return $this->stock >= $quantite;
    }

    public function scopeInCategorie(Builder $query, int $categorieId): Builder
    {
        return $query->where('categorie_id', $categorieId);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function ($q) use ($search) {
            $q->where(function ($q2) use ($search) {
                $q2->where('nom', 'like', "%{$search}%")
                   ->orWhere('description', 'like', "%{$search}%");
            });
        });
    }

    public function scopePrixBetween(Builder $query, ?float $min, ?float $max): Builder
    {
        return $query->when($min !== null, fn ($q) => $q->where('prix', '>=', $min))
                     ->when($max !== null, fn ($q) => $q->where('prix', '<=', $max));
    }

    // ======================
    // MEDIA CONVERSIONS
    // ======================

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(180)
            ->height(180)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('small')
            ->width(60)
            ->height(60)
            ->sharpen(10)
            ->nonQueued();
    }

    // ======================
    // ACCESSORS
    // ======================

    /**
     * URL contrôlée de l'image (le disque public tenant n'est pas exposé
     * via `/storage`). Utilisable aussi pour un produit archivé (historique
     * de commande) : l'image reste lisible.
     */
    public function imageUrl(?string $conversion = null): ?string
    {
        if (! $this->getFirstMedia('image_principale')) {
            return null;
        }

        $url = '/api/public/produits/'.$this->slug.'/image';

        return $conversion !== null ? $url.'?conversion='.$conversion : $url;
    }

    public function getImageUrlAttribute(): string
    {
        return $this->imageUrl('thumb') ?: asset('assets/img/logo.png');
    }

    public function getImageOriginalAttribute(): string
    {
        return $this->imageUrl() ?: asset('assets/img/logo.png');
    }
}
