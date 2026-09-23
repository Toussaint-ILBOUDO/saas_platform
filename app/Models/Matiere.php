<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matiere extends Model
{
    protected $fillable = [
        'nom',
        'sigle',
        'description',
        'actif',
    ];

    /**
     * Enseignants compétents
     */
    public function enseignants()
    {
        return $this->belongsToMany(
            EnseignantProfil::class,
            'enseignant_matiere',
            'matiere_id',
            'enseignant_profil_id'
        );
    }

    /**
     * Affectations de cours
     */
    public function affectations()
    {
        return $this->hasMany(
            AffectationEnseignant::class
        );
    }

    /**
     * Objectifs pédagogiques
     */
    public function objectifs()
    {
        return $this->hasMany(
            ObjectifMatiere::class
        );
    }

    /**
     * Demandes publiques
     */
    public function demandesCours()
    {
        return $this->belongsToMany(
            DemandeCours::class,
            'demande_cours_matieres',
            'matiere_id',
            'demande_cours_id'
        );
    }
}