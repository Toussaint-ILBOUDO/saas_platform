<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RapportSection extends Model
{
    protected $table = 'rapport_sections';

    protected $fillable = [
        'libelle',
        'description',
        'ordre',
        'actif',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'actif' => 'boolean',
    ];

    public function elements(): HasMany
    {
        return $this->hasMany(RapportElement::class, 'section_id')
            ->orderBy('ordre');
    }
}