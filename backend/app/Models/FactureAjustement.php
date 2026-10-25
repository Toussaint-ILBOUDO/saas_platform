<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureAjustement extends Model
{
    protected $fillable = [
        'facture_id',
        'type_ajustement_id',
        'type',
        'libelle',
        'montant',
    ];

    protected $casts = [
        'montant' => 'integer',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function typeAjustement(): BelongsTo
    {
        return $this->belongsTo(
            TypeAjustement::class,
            'type_ajustement_id'
        );
    }

    // ======================
    // HELPERS
    // ======================

    public function estCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function estDebit(): bool
    {
        return $this->type === 'debit';
    }
}
