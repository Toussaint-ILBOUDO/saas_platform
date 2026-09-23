<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategorieProduit extends Model
{
    use SoftDeletes;

    protected $table = 'categorie_produits';

    protected $fillable = [
        'nom',
        'slug',
        'description',
        'ordre',
        'is_active',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CategorieProduit $categorie) {
            if (empty($categorie->slug)) {
                $categorie->slug = $categorie->generateUniqueSlug($categorie->nom);
            }
        });

        static::updating(function (CategorieProduit $categorie) {
            if ($categorie->isDirty('nom') && empty($categorie->slug)) {
                $categorie->slug = $categorie->generateUniqueSlug($categorie->nom);
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

    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class, 'categorie_id');
    }

    // ======================
    // SCOPES
    // ======================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderBy('nom');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function ($q) use ($search) {
            $q->where('nom', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // ======================
    // ACCESSORS
    // ======================

    public function getProduitsActifsCountAttribute(): int
    {
        return $this->produits()->active()->count();
    }
}
