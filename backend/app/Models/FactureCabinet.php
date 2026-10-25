<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Facture d'un cabinet de la plateforme (une facture ne couvre qu'un
 * cabinet et une période, base centrale Landlord).
 */
class FactureCabinet extends Model
{
    use CentralConnection;

    public const STATUT_BROUILLON = 'brouillon';
    public const STATUT_EMISE = 'emise';
    public const STATUT_PARTIELLEMENT_PAYEE = 'partiellement_payee';
    public const STATUT_PAYEE = 'payee';
    public const STATUT_ANNULEE = 'annulee';

    public const STATUTS = [
        self::STATUT_BROUILLON,
        self::STATUT_EMISE,
        self::STATUT_PARTIELLEMENT_PAYEE,
        self::STATUT_PAYEE,
        self::STATUT_ANNULEE,
    ];

    protected $table = 'factures_cabinet';

    protected $guarded = [];

    protected $casts = [
        'periode_debut' => 'date',
        'periode_fin' => 'date',
        'emise_le' => 'datetime',
        'annulee_le' => 'datetime',
        'date_echeance' => 'date',
    ];

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneFactureCabinet::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementCabinet::class);
    }

    public function evenements(): HasMany
    {
        return $this->hasMany(EvenementFacturable::class);
    }

    /** Cabinet central (sans FK, identifiant string dans tenants). */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id', 'id');
    }

    /** Somme des débits − somme des crédits des lignes. */
    public function montantCalcule(): int
    {
        return (int) $this->lignes()
            ->get(['sens', 'montant'])
            ->reduce(fn (int $total, LigneFactureCabinet $l) => $total + ($l->sens === 'debit' ? $l->montant : -$l->montant), 0);
    }

    public function resteAPayer(): int
    {
        return max(0, $this->montant_total - $this->montant_paye);
    }

    public function estEmise(): bool
    {
        return in_array($this->statut, [self::STATUT_EMISE, self::STATUT_PARTIELLEMENT_PAYEE, self::STATUT_PAYEE]);
    }

    public function estBrouillon(): bool
    {
        return $this->statut === self::STATUT_BROUILLON;
    }

    public function estPartiellementPayee(): bool
    {
        return $this->statut === self::STATUT_PARTIELLEMENT_PAYEE;
    }

    public function estPayee(): bool
    {
        return $this->statut === self::STATUT_PAYEE;
    }

    public function estAnnulee(): bool
    {
        return $this->statut === self::STATUT_ANNULEE;
    }
}
