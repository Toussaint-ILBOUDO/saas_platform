<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementEnseignant extends Model
{
    protected $fillable = [
        'enseignant_id',
        'periode_id',
        'contrat_cours_id',
        'total_heures_effectuees',
        'montant_total',
        'statut',
        'transaction_reference',
        'date_paiement',
    ];

    protected $casts = [
        'total_heures_effectuees' => 'decimal:2',
        'montant_total' => 'integer',
        'date_paiement' => 'datetime',
    ];

    public function contrat()
    {
        return $this->belongsTo(
            ContratCours::class,
            'contrat_cours_id'
        );
    }

    public function enseignant()
    {
        return $this->belongsTo(
            EnseignantProfil::class,
            'enseignant_id'
        );
    }

    public function periode()
    {
        return $this->belongsTo(
            PeriodeComptable::class,
            'periode_id'
        );
    }

    public function lignes()
    {
        return $this->hasMany(
            LignePaiementEnseignant::class
        );
    }
}