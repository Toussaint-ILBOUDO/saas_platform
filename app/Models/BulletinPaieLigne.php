<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulletinPaieLigne extends Model
{
    protected $fillable = [
        'bulletin_paie_id',
        'affectation_enseignant_id',
        'contrat_cours_id',
        'eleve_id',
        'matiere_id',
        'nombre_heures',
        'taux_horaire',
        'montant',
    ];

    protected $casts = [
        'nombre_heures' => 'decimal:2',
        'taux_horaire'  => 'integer',
        'montant'       => 'integer',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(
            BulletinPaie::class,
            'bulletin_paie_id'
        );
    }

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(
            AffectationEnseignant::class,
            'affectation_enseignant_id'
        );
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(
            ContratCours::class,
            'contrat_cours_id'
        );
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }
}
