<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactureCabinet extends Model
{
    protected $fillable = [
        'numero',
        'periode_debut',
        'periode_fin',
        'total_cours',
        'total_ventes',
        'total_inscriptions',
        'montant_commission',
        'montant_total_du',
        'statut',
        'date_facture',
        'date_paiement',
    ];

    protected $casts = [
        'periode_debut'    => 'date',
        'periode_fin'      => 'date',
        'date_facture'     => 'date',
        'date_paiement'    => 'date',
        'total_cours'      => 'integer',
        'total_ventes'     => 'integer',
        'total_inscriptions' => 'integer',
        'montant_commission' => 'integer',
        'montant_total_du'   => 'integer',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneFactureCabinet::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementCabinet::class);
    }

    // ======================
    // HELPERS
    // ======================

    public function estPayee(): bool
    {
        return $this->statut === 'payee';
    }

    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function estPartiellementPayee(): bool
    {
        return $this->statut === 'partiel';
    }

    public function estAnnulee(): bool
    {
        return $this->statut === 'annulee';
    }

    public function getMontantPayeAttribute(): int
    {
        if ($this->relationLoaded('paiements')) {
            return (int) $this->paiements
                ->where('statut', 'valide')
                ->sum('montant_paye');
        }

        if (array_key_exists('montant_paye', $this->attributes)) {
            return (int) $this->attributes['montant_paye'];
        }

        return (int) $this->paiements()
            ->where('statut', 'valide')
            ->sum('montant_paye');
    }

    public function getMontantRestantAttribute(): int
    {
        return $this->montant_total_du - $this->montant_paye;
    }

    /**
     * Génère un numéro de facture unique.
     * Format : FCAB-YYYYMM-NNNNN
     */
    public static function genererNumero(): string
    {
        $prefixe = 'FCAB-' . now()->format('Ym') . '-';

        $dernierNumero = self::query()
            ->where('date_facture', '>=', now()->startOfMonth())
            ->where('date_facture', '<', now()->addMonth()->startOfMonth())
            ->count();

        return $prefixe
            . str_pad($dernierNumero + 1, 5, '0', STR_PAD_LEFT);
    }
}