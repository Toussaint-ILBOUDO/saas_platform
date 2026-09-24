<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemoignageSignalement extends Model
{
    protected $fillable = ['temoignage_id', 'user_id', 'motif', 'description', 'statut'];

    public function temoignage(): BelongsTo
    {
        return $this->belongsTo(Temoignage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
