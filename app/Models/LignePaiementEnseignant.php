<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LignePaiementEnseignant extends Model
{
    protected $fillable = [
        'paiement_enseignant_id',
        'affectation_enseignant_id',
        'nombre_heures',
        'montant',
    ];

    public function paiement()
    {
        return $this->belongsTo(
            PaiementEnseignant::class
        );
    }

    public function affectation()
    {
        return $this->belongsTo(
            AffectationEnseignant::class,
            'affectation_enseignant_id'
        );
    }
}