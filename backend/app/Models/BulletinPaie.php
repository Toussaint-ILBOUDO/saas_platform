<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulletinPaie extends Model
{
    protected $table = 'bulletins_paie';

    protected $fillable = [
        'numero',
        'enseignant_id',
        'periode_id',
        'total_heures',
        'montant_brut',
        'frais_suivi',
        'montant_net',
        'statut',
        'commentaire_enseignant',
        'date_consultation',
        'date_validation',
        'date_paiement',
        'mode_paiement',
        'reference_paiement',
    ];

    protected $casts = [
        'total_heures'      => 'decimal:2',
        'montant_brut'      => 'integer',
        'frais_suivi'       => 'integer',
        'montant_net'       => 'integer',
        'date_consultation' => 'datetime',
        'date_validation'   => 'datetime',
        'date_paiement'     => 'date',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(
            EnseignantProfil::class,
            'enseignant_id'
        );
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(
            PeriodeComptable::class,
            'periode_id'
        );
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(
            BulletinPaieLigne::class,
            'bulletin_paie_id'
        );
    }

    public function ajustements(): HasMany
    {
        return $this->hasMany(
            BulletinPaieAjustement::class,
            'bulletin_paie_id'
        );
    }

    // ======================
    // HELPERS
    // ======================

    public function estBrouillon(): bool
    {
        return $this->statut === 'brouillon';
    }

    public function estGenere(): bool
    {
        return $this->statut === 'genere';
    }

    public function estConsulte(): bool
    {
        return $this->statut === 'consulte';
    }

    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }

    public function estVerse(): bool
    {
        return $this->statut === 'verse';
    }

    public function estConteste(): bool
    {
        return $this->statut === 'conteste';
    }

    public function estCorrige(): bool
    {
        return $this->statut === 'corrige';
    }

    public function getTotalPrimesAttribute(): int
    {
        return $this->ajustements
            ->where('type', 'prime')
            ->sum('montant');
    }

    public function getTotalRetenuesAttribute(): int
    {
        return $this->ajustements
            ->where('type', 'retenue')
            ->sum('montant');
    }

    public function getMontantNetFinalAttribute(): int
    {
        return $this->montant_brut
            - $this->frais_suivi
            + $this->total_primes
            - $this->total_retenues;
    }
}
