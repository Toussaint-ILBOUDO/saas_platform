<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObjectifPedagogique extends Model
{
    protected $table = 'objectif_pedagogiques';

    protected $fillable = [
        'eleve_id',
        'periode',
        'moyenne_visee',
        'moyenne_obtenue',
        'materiel_disponible',
        'materiel_manquant',
        'commentaire_admin',
    ];

    protected $casts = [
        'moyenne_visee' => 'decimal:2',
        'moyenne_obtenue' => 'decimal:2',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function objectifsMatieres(): HasMany
    {
        return $this->hasMany(ObjectifMatiere::class);
    }
}