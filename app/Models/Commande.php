<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Commande extends Model
{
    use SoftDeletes;

    protected $table = 'commandes';

    /**
     * Préfixe des références publiques de commande.
     */
    public const PREFIXE_REFERENCE = 'CMD-';

    protected $fillable = [
        'reference',
        'user_id',
        'nom_client',
        'telephone_client',
        'whatsapp',
        'adresse_livraison',
        'quartier',
        'is_livraison',
        'montant_total',
        'frais_livraison',
        'token',
        'statut',
        'mode_paiement',
        'reference_transaction',
        'notes',
    ];

    protected $casts = [
        'is_livraison' => 'boolean',
        'montant_total' => 'decimal:2',
        'frais_livraison' => 'decimal:2',
        'statut_modifications' => 'array',
    ];

    // ======================
    // STATUTS
    // ======================

    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_CONFIRMEE = 'confirmee';
    const STATUT_EN_PREPARATION = 'en_preparation';
    const STATUT_LIVREE = 'livree';
    const STATUT_ANNULEE = 'annulee';

    const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_CONFIRMEE,
        self::STATUT_EN_PREPARATION,
        self::STATUT_LIVREE,
        self::STATUT_ANNULEE,
    ];

    // ======================
    // RELATIONS
    // ======================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'commande_id');
    }

    // ======================
    // SCOPES
    // ======================

    public function scopeStatut(Builder $query, string $statut): Builder
    {
        return $query->where('statut', $statut);
    }

    public function scopeDuJour(Builder $query): Builder
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function ($q) use ($search) {
            $q->where(function ($q2) use ($search) {
                $q2->where('nom_client', 'like', "%{$search}%")
                   ->orWhere('telephone_client', 'like', "%{$search}%")
                   ->orWhere('whatsapp', 'like', "%{$search}%")
                   ->orWhere('reference', 'like', "%{$search}%");
            });
        });
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // ======================
    // HELPERS
    // ======================

    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function estConfirmee(): bool
    {
        return $this->statut === self::STATUT_CONFIRMEE;
    }

    public function estEnPreparation(): bool
    {
        return $this->statut === self::STATUT_EN_PREPARATION;
    }

    public function estLivree(): bool
    {
        return $this->statut === self::STATUT_LIVREE;
    }

    public function estAnnulee(): bool
    {
        return $this->statut === self::STATUT_ANNULEE;
    }

    public function getMontantAvecLivraisonAttribute(): float
    {
        return (float) $this->montant_total + (float) $this->frais_livraison;
    }

    public static function statutLabel(?string $statut): string
    {
        return match ($statut) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_CONFIRMEE => 'Confirmée',
            self::STATUT_EN_PREPARATION => 'En préparation',
            self::STATUT_LIVREE => 'Livrée',
            self::STATUT_ANNULEE => 'Annulée',
            default => 'Inconnu',
        };
    }

    public static function statutBadgeClass(?string $statut): string
    {
        return match ($statut) {
            self::STATUT_EN_ATTENTE => 'bg-warning',
            self::STATUT_CONFIRMEE => 'bg-info',
            self::STATUT_EN_PREPARATION => 'bg-primary',
            self::STATUT_LIVREE => 'bg-success',
            self::STATUT_ANNULEE => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function enregistrerModificationStatut(string $ancienStatut, string $nouveauStatut, ?int $modifiePar = null): void
    {
        $modifications = $this->statut_modifications ?? [];

        $modifications[] = [
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'modifie_par' => $modifiePar,
            'created_at' => now()->toIso8601String(),
        ];

        $this->statut_modifications = $modifications;
        $this->save();
    }

    public function scopeWithTotalArticles(Builder $query): Builder
    {
        return $query->withSum(['lignes as total_articles'], 'quantite');
    }

    /*
    |--------------------------------------------------------------------------
    | Référence publique aléatoire
    |--------------------------------------------------------------------------
    */

    /**
     * Génère une référence « CMD-XXXXXXXX » aléatoire et garantie unique.
     */
    public static function genererReference(): string
    {
        do {
            $reference = self::PREFIXE_REFERENCE.Str::upper(Str::random(8));
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    protected static function booted(): void
    {
        static::creating(function (Commande $commande) {
            if (empty($commande->reference)) {
                $commande->reference = static::genererReference();
            }
        });
    }
}
