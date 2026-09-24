<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Eleve;
use App\Models\DemandeCours;

class Classe extends Model
{
    protected $fillable = [
        'nom',
        'sigle',
    ];

    public function eleves(): HasMany
    {
        return $this->hasMany(Eleve::class);
    }

    public function demandesCours(): HasMany
    {
        return $this->hasMany(DemandeCours::class);
    }

    // public function documents(): HasMany
    // {
    //     return $this->hasMany(DocumentBibliotheque::class);
    // }
}