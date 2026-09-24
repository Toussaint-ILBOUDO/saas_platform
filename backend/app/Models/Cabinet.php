<?php

namespace App\Models;

use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class Cabinet extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    protected $fillable = [
        'id',
        'nom',
        'sous_domaine',
        'status',
        'logo',
        'theme',
        'data',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'nom',
            'sous_domaine',
            'status',
            'logo',
            'theme',
        ];
    }
}