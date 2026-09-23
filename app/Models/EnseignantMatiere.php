<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EnseignantMatiere extends Model
{
    use HasFactory;

    protected $table = 'enseignant_matiere';

    protected $fillable = [
        'enseignant_profil_id',
        'matiere_id',
    ];

    /**
     * Enseignant concerné
     */
    public function enseignant()
    {
        return $this->belongsTo(EnseignantProfil::class, 'enseignant_profil_id');
    }

    /**
     * Matière associée
     */
    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }
}