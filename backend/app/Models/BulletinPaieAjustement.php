<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulletinPaieAjustement extends Model
{
    protected $fillable = [
        'bulletin_paie_id',
        'type_ajustement_id',
        'type',
        'libelle',
        'montant',
    ];

    protected $casts = [
        'montant' => 'integer',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(
            BulletinPaie::class,
            'bulletin_paie_id'
        );
    }

    public function typeAjustement(): BelongsTo
    {
        return $this->belongsTo(
            TypeAjustement::class,
            'type_ajustement_id'
        );
    }
}
