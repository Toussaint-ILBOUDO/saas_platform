<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneFacture extends Model
{
    protected $fillable = [
        'facture_id',
        'affectation_enseignant_id',
        'nombre_heures',
        'taux_horaire',
        'montant',
    ];

    protected $casts = [
        'nombre_heures' => 'decimal:2',
        'taux_horaire'  => 'integer',
        'montant'       => 'integer',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(
            AffectationEnseignant::class,
            'affectation_enseignant_id'
        );
    }
}
