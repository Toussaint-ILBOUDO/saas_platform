<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeCommission extends Model
{
    protected $fillable = [
        'nom_du_type',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneFactureCabinet::class);
    }
}