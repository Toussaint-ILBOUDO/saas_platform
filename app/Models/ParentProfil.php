<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentProfil extends Model
{
    protected $table = 'parent_profils';

    protected $fillable = [
        'user_id',
        'adresse_domicile',
        'profession',
        'nombre_enfants',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}