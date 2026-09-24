<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RapportMensuelEnseignant extends Model
{
    protected $fillable = [
        'contrat_cours_id',
        'enseignant_id',
        'periode_id',
        'volume_horaire_cumule',
        'point_notes_matieres',
        'point_notes_autres_matieres',
        'bilan_activites',
        'difficultes_rencontrees',
        'solutions_trouvees',
        'attentes_parents_eleve',
        'attentes_administration',
        'appreciation_evolution',
        'observations',
        'statut',
    ];

    protected $casts = [
        'volume_horaire_cumule' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function contratCours()
    {
        return $this->belongsTo(ContratCours::class);
    }

    public function enseignant()
    {
        return $this->belongsTo(
            EnseignantProfil::class,
            'enseignant_id',
            'id'
        );
    }

    public function periode()
    {
        return $this->belongsTo(PeriodeComptable::class,'periode_id');
    }

    
}