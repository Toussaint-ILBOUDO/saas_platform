<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\DemandeCours;
use App\Models\ContratCours;

class TypeCours extends Model
{
    protected $table = 'type_cours';

    protected $fillable = [
        'libelle',
        'code',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeCours::class);
    }

    public function contrats(): HasMany
    {
        return $this->hasMany(ContratCours::class);
    }
}