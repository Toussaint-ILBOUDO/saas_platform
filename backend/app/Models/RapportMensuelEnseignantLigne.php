<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * D-049 — Ligne de ventilation d'un rapport mensuel : une par matière
 * (donc par affectation enseignant/contrat/matière).
 *
 * Le rapport conserve aussi `volume_horaire_cumule` (total), mais c'est cette
 * table qui fait foi pour les montants : la facture et le bulletin consomment
 * les mêmes lignes, ce qui rend leurs sommes identiques par construction.
 */
class RapportMensuelEnseignantLigne extends Model
{
    protected $table = 'rapport_mensuel_enseignant_lignes';

    protected $fillable = [
        'rapport_mensuel_enseignant_id',
        'affectation_enseignant_id',
        'matiere_id',
        'nombre_seances',
        'nombre_heures',
    ];

    protected $casts = [
        'nombre_heures' => 'decimal:2',
        'nombre_seances' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function rapportMensuel()
    {
        return $this->belongsTo(RapportMensuelEnseignant::class, 'rapport_mensuel_enseignant_id');
    }

    public function affectation()
    {
        return $this->belongsTo(AffectationEnseignant::class, 'affectation_enseignant_id');
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

    public function enseignant()
    {
        return $this->belongsTo(EnseignantProfil::class, 'enseignant_id', 'id')
            ->through(AffectationEnseignant::class, 'id', 'affectation_enseignant_id', 'enseignant_id');
    }
}