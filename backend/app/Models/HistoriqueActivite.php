<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrée d'audit du cabinet (tenant) — M13.
 */
class HistoriqueActivite extends Model
{
    protected $table = 'historique_activites';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'contexte' => 'array',
        'created_at' => 'datetime',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
