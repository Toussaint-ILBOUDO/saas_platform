<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class Cabinet extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    protected $keyType = 'string';

    /**
     * L'id est fourni manuellement (slug D-005) : jamais d'auto-incrément,
     * sinon stancl insérerait une ligne sans id (id varchar → 0).
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    protected $fillable = [
        'id',
        'nom',
        'sous_domaine',
        'status',
        'logo',
        'theme',
        'data',
        // stancl VirtualColumn : sérialisés dans la colonne json « data »
        'email',
        'telephone',
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

    public function parametres(): HasOne
    {
        return $this->hasOne(ParametresCabinet::class, 'cabinet_id', 'id');
    }

    /**
     * Premier domaine (ex. c1.localhost) — UI du Landlord.
     */
    public function getPrimaryDomainAttribute(): ?string
    {
        return $this->domains()->value('domain');
    }
}