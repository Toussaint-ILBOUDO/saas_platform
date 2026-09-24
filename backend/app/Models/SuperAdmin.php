<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Super-admin de la plateforme (D-015). Toujours basé sur la connexion
 * centrale (jamais une base cabinet), quel que soit le contexte tenant.
 */
class SuperAdmin extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use CentralConnection;

    protected $fillable = [
        'nom',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}