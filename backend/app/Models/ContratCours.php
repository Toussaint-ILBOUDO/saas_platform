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

    /**
     * Sans ces casts, `date_debut` reste une chaîne et l'API doit la convertir à
     * la main (`Carbon::parse`) — donc renvoyait une 500 dès qu'une date était
     * saisie. Les vues, qui font déjà `Carbon::parse(...)`, continuent de
     * fonctionner à l'identique.
     */
    protected $casts = [
        'autres_frais_suivi' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public const ACTIF = 'actif';

    public const SUSPENDU = 'suspendu';

    public const TERMINE = 'termine';

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