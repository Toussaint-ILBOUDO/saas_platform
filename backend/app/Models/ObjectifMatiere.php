<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObjectifMatiere extends Model
{
    protected $table = 'objectif_matieres';

    protected $fillable = [
        'objectif_pedagogique_id',
        'matiere_id',
        'moyenne_visee',
        'moyenne_obtenue',
        'commentaire',
    ];

    // Relations

    public function objectifPedagogique()
    {
        return $this->belongsTo(ObjectifPedagogique::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }
}