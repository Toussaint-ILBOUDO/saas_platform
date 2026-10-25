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
        'prenom_client',
        'telephone_client',
        'whatsapp',
        'adresse_livraison',
        'quartier',
        'point_de_repere',
        'lien_geolocalisation',
        'is_livraison',
        'montant_total',
        'frais_livraison',
        'token',
        'cle_idempotence',
        'statut',
        'mode_paiement',
        'reference_transaction',
        'notes',
        'note_interne',
        'annulation_demandee_at',
        'annulation_motif',
    ];

    protected $casts = [
        'is_livraison' => 'boolean',
        'montant_total' => 'decimal:2',
        'frais_livraison' => 'decimal:2',
        'statut_modifications' => 'array',
        'annulation_demandee_at' => 'datetime',
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

    /**
     * Une commande peut être annulée par le client tant qu'elle n'est pas
     * entrée en préparation (aucun déplacement ni préparation engagé).
     */
    public function estAnnulableParClient(): bool
    {
        return in_array($this->statut, [self::STATUT_EN_ATTENTE, self::STATUT_CONFIRMEE], true);
    }

    /**
     * Étapes publiques du suivi : chaque statut franchi est daté à partir
     * du journal `statut_modifications`, les étapes futures restent vides.
     *
     * `fait` vaut true lorsque l'étape est franchie : date présente dans le
     * journal, ou étape correspondant au statut courant (journal incomplet).
     *
     * @return array<int, array{cle: string, libelle: string, statut: string, date: string|null, fait: bool}>
     */
    public function etapesSuivi(): array
    {
        $journal = collect($this->statut_modifications ?? []);

        $dateDe = function (string $statut) use ($journal) {
            $evenement = $journal->last(fn ($m) => ($m['nouveau_statut'] ?? null) === $statut);

            return $evenement['created_at'] ?? null;
        };

        $etapes = [
            ['cle' => 'recue', 'libelle' => 'Commande reçue', 'statut' => self::STATUT_EN_ATTENTE, 'date' => $this->created_at?->toIso8601String()],
            ['cle' => 'confirmee', 'libelle' => 'Commande confirmée', 'statut' => self::STATUT_CONFIRMEE, 'date' => $dateDe(self::STATUT_CONFIRMEE)],
            ['cle' => 'preparation', 'libelle' => 'En préparation', 'statut' => self::STATUT_EN_PREPARATION, 'date' => $dateDe(self::STATUT_EN_PREPARATION)],
            ['cle' => 'livree', 'libelle' => $this->is_livraison ? 'Livrée' : 'Prête / retirée', 'statut' => self::STATUT_LIVREE, 'date' => $dateDe(self::STATUT_LIVREE)],
        ];

        if ($this->estAnnulee()) {
            $etapes[] = [
                'cle' => 'annulee',
                'libelle' => 'Annulée',
                'statut' => self::STATUT_ANNULEE,
                'date' => $dateDe(self::STATUT_ANNULEE),
            ];
        }

        foreach ($etapes as $i => $etape) {
            $etapes[$i]['fait'] = $etape['date'] !== null || $etape['statut'] === $this->statut;
        }

        return $etapes;
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
     * Génère la référence publique « SIGLE-AAAAMMJJ-IDENTIFIANT »
     * (ex. `CAB-20261010-A67BR234`) : lisible, identifiable, sans lien avec
     * l'id interne, et suffisamment aléatoire pour être devinable une fois
     * sur des milliards. La contrainte d'unicité + la boucle garantissent
     * l'unicité même en cas de collision théorique.
     */
    public static function genererReference(): string
    {
        $sigle = \App\Models\ParametreLibrairie::sigle();
        $date = now()->format('Ymd');

        do {
            $reference = sprintf('%s-%s-%s', $sigle, $date, Str::upper(Str::random(7)));
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
