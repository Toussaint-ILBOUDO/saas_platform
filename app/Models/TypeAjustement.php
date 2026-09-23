<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeAjustement extends Model
{
    protected $fillable = [
        'libelle',
        'direction',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ajustements(): HasMany
    {
        return $this->hasMany(
            BulletinPaieAjustement::class,
            'type_ajustement_id'
        );
    }

    public function estCredit(): bool
    {
        return $this->direction === 'credit';
    }

    public function estDebit(): bool
    {
        return $this->direction === 'debit';
    }
}
