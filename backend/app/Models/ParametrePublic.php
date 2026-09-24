<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametrePublic extends Model
{
    protected $table = 'parametres_publics';

    protected $fillable = [
        'theme',
        'footer',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'theme' => 'array',
            'footer' => 'array',
            'data' => 'array',
        ];
    }
}