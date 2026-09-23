<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriBibliotheque extends Model
{
    protected $fillable = ['user_id', 'document_bibliotheque_id'];

    protected $table = 'favori_bibliotheques';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentBibliotheque::class, 'document_bibliotheque_id');
    }
}
