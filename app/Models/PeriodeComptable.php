<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeComptable extends Model
{
    protected $fillable = [
        'label',
        'date_debut',
        'date_fin',
        'type',
        'statut',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];
}