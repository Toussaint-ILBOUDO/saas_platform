<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneFactureCabinet extends Model
{
    protected $fillable = [
        'facture_cabinet_id',
        'type_commission_id',
        'quantite',
        'base_calcul',
        'taux_commission',
        'montant',
    ];

    protected $casts = [
        'quantite'        => 'integer',
        'base_calcul'     => 'integer',
        'taux_commission' => 'decimal:2',
        'montant'         => 'integer',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function factureCabinet(): BelongsTo
    {
        return $this->belongsTo(FactureCabinet::class);
    }

    public function typeCommission(): BelongsTo
    {
        return $this->belongsTo(TypeCommission::class);
    }
}