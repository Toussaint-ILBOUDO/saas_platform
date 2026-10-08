<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RapportElement extends Model
{
    protected $table = 'rapport_elements';

    protected $fillable = [
        'section_id',
        'libelle',
        'type',
        'obligatoire',
        'aide',
        'ordre',
        'actif',
    ];

    protected $casts = [
        'obligatoire' => 'boolean',
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(RapportSection::class, 'section_id');
    }
}