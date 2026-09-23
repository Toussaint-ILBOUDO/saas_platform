<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eleve extends Model
{
    protected $fillable = [
        'user_id',
        'parent_id',
        'classe_id',
        'ecole',
        'date_naissance',
        'lieu_naissance',
        'parent_charge',
        'etablissement_origine',
        'profession_pere',
        'profession_mere',
        'regime_etude',
        'loisirs_sport',
        'religion_enfant',
        'maladies_allergies',
        'interdits_familiaux',
        'boisson_preferee',
        'nourriture_preferee',
        'autres_precautions',
        'autres_observations',
        'statut',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function contrats(): HasMany
    {
        return $this->hasMany(
            ContratCours::class
        );
    }

    public function objectifs(): HasMany
    {
        return $this->hasMany(
            ObjectifPedagogique::class
        );
    }
}