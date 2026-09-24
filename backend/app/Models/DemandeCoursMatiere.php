<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandeCoursMatiere extends Model
{
    protected $fillable = [
        'demande_cours_id',
        'matiere_id',
    ];

    public function demandeCours()
    {
        return $this->belongsTo(DemandeCours::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }
}