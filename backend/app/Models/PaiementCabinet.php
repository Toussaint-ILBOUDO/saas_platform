<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Encaissement d'une facture de cabinet. */
class PaiementCabinet extends Model
{
    protected $table = 'paiements_cabinet';

    protected $guarded = [];

    protected $casts = [
        'montant' => 'integer',
        'date_paiement' => 'datetime',
    ];
}
