<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandeCours extends Model
{
    protected $table = 'demande_cours';

    protected $fillable = [
        'nom_parent',
        'prenom_parent',
        'telephone',
        'type_cours_id',
        'classe_id',
        'volume_horaire_estime',
        'statut',
        'message',
    ];

    // Relations

    public function typeCours()
    {
        return $this->belongsTo(TypeCours::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function matieres()
    {
        return $this->belongsToMany(
            Matiere::class,
            'demande_cours_matieres'
        );
    }
}