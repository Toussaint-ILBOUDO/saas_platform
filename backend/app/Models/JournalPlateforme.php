<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Journal de la plateforme (Landlord, base centrale).
 */
class JournalPlateforme extends Model
{
    use CentralConnection;

    protected $table = 'journal_plateforme';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'contexte' => 'array',
    ];

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id', 'id');
    }

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'super_admin_id', 'id');
    }

    /**
     * Écrit une entrée au journal (contexte central).
     */
    public static function ecrire(
        string $action,
        string $level = 'info',
        ?Cabinet $cabinet = null,
        array $contexte = []
    ): self {
        return self::create([
            'action' => $action,
            'level' => $level,
            'cabinet_id' => $cabinet?->id,
            'super_admin_id' => auth('landlord')->id() ?: null,
            'contexte' => $contexte,
        ]);
    }
}