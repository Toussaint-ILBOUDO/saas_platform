<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Paramètres de facturation et fonctionnalités actives d'un cabinet
 * (base centrale Landlord, D-002/D-031).
 */
class ParametresCabinet extends Model
{
    use CentralConnection;

    protected $table = 'parametres_cabinet';

    protected $guarded = [];

    protected $casts = [
        'tarif_abonnement' => 'float',
        'tarif_par_eleve' => 'float',
        'fonctionnalites_activees' => 'array',
    ];

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id', 'id');
    }
}