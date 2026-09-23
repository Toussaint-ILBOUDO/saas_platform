<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffectationEnseignant extends Model
{
    protected $table = 'affectation_enseignants';

    protected $fillable = [
        'contrat_cours_id',
        'enseignant_id',
        'matiere_id',
        'taux_horaire_enseignant',
        'nombre_heures_prevues',
        'date_affectation',
        'date_fin',
        'statut',
    ];

    // Relations

    public function contrat()
    {
        return $this->belongsTo(ContratCours::class, 'contrat_cours_id');
    }

    public function enseignant()
    {
        return $this->belongsTo(EnseignantProfil::class, 'enseignant_id');
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }

    public function cahiersTexte()
    {
        return $this->hasMany(CahierTexte::class);
    }

    public function lignesFacture()
    {
        return $this->hasMany(LigneFacture::class);
    }

    public function lignesPaiement()
    {
        return $this->hasMany(LignePaiementEnseignant::class);
    }
}