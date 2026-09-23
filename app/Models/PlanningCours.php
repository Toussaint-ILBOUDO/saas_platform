<?php

namespace App\Models;

use App\Modules\Pedagogie\Enums\PlanningJourSemaine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningCours extends Model
{
    protected $table = 'planning_cours';

    protected $fillable = [
        'enseignant_id',
        'affectation_enseignant_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
    ];

    protected $casts = [
        'jour_semaine' => 'integer',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(EnseignantProfil::class, 'enseignant_id');
    }

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(AffectationEnseignant::class, 'affectation_enseignant_id');
    }

    public function getJourLabelAttribute(): string
    {
        return PlanningJourSemaine::label($this->jour_semaine);
    }

    public function getTrancheHoraireAttribute(): string
    {
        return $this->heure_debut.' - '.$this->heure_fin;
    }
}