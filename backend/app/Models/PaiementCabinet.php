<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaiementCabinet extends Model
{
    protected $fillable = [
        'facture_cabinet_id',
        'montant_paye',
        'mode_paiement',
        'reference_paiement',
        'date_paiement',
        'statut',
    ];

    protected $casts = [
        'montant_paye' => 'integer',
        'date_paiement' => 'date',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function factureCabinet(): BelongsTo
    {
        return $this->belongsTo(FactureCabinet::class);
    }

    // ======================
    // HELPERS
    // ======================

    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }

    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function estAnnule(): bool
    {
        return $this->statut === 'annule';
    }
}