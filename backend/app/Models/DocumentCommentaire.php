<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentCommentaire extends Model
{
    protected $fillable = ['user_id', 'document_bibliotheque_id', 'parent_id', 'contenu', 'signale'];

    protected $casts = [
        'signale' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentBibliotheque::class, 'document_bibliotheque_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocumentCommentaire::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(DocumentCommentaire::class, 'parent_id');
    }
}
