<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActualiteReaction extends Model
{
    protected $table = 'actualite_reactions';

    protected $fillable = [
        'actualite_id',
        'user_id',
        'reaction',
        'ip',
    ];

    public function actualite(): BelongsTo
    {
        return $this->belongsTo(Actualite::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
