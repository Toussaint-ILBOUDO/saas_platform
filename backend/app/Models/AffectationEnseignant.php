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

    /**
     * `nombre_heures_prevues` est un decimal(5,2) et le taux un entier de
     * FCFA : sans ces casts, l'API comparait des chaînes et la facturation
     * recalculait des montants à partir de texte.
     */
    protected $casts = [
        'taux_horaire_enseignant' => 'integer',
        'nombre_heures_prevues' => 'decimal:2',
        'date_affectation' => 'date',
        'date_fin' => 'date',
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

    /**
     * D-048 : la rémunération de l'enseignant passe désormais par les lignes
     * de son bulletin de paie, plus par un paiement par affectation.
     *
     * @see \App\Models\BulletinPaieLigne
     */
    public function lignesBulletin()
    {
        return $this->hasMany(BulletinPaieLigne::class);
    }
}