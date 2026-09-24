<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
use App\Models\Matiere;

class EnseignantProfil extends Model
{
    protected $table = 'enseignant_profils';

    protected $fillable = [
        'user_id',
        'numero_orange_money',
        'diplome_max',
        'lieu_de_service',
        'domicile',
        'frais_annuel_regle',
    ];

    protected $casts = [
        'frais_annuel_regle' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matieres(): BelongsToMany
    {
        return $this->belongsToMany(Matiere::class, 'enseignant_matiere', 'enseignant_profil_id', 'matiere_id');
    }

    public function rapportsMensuels()
    {
        return $this->hasMany(
            RapportMensuelEnseignant::class,
            'enseignant_id'
        );
    }
}