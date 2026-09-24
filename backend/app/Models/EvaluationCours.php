<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationCours extends Model
{
    protected $table = 'evaluation_cours';

    protected $fillable = [
        'eleve_id',
        'enseignant_id',
        'auteur_id',
        'note',
        'commentaire',
        'anonyme',
    ];

    // Relations

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function enseignant()
    {
        return $this->belongsTo(EnseignantProfil::class, 'enseignant_id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }
}