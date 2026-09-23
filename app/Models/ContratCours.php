<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratCours extends Model
{
    protected $table = 'contrat_cours';

    protected $fillable = [
        'eleve_id',
        'type_cours_id',
        'autres_frais_suivi',
        'statut',
        'date_debut',
        'date_fin',
        'notes_admin',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function typeCours()
    {
        return $this->belongsTo(TypeCours::class);
    }

    public function affectations()
    {
        return $this->hasMany(AffectationEnseignant::class, 'contrat_cours_id');
    }

    public function rapportsMensuels()
    {
        return $this->hasMany(
            RapportMensuelEnseignant::class,
            'contrat_cours_id'
        );
    }

}