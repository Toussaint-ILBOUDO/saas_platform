<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemoignageCommentaire extends Model
{
    protected $fillable = ['temoignage_id', 'user_id', 'parent_id', 'contenu', 'signale'];

    protected function casts(): array
    {
        return [
            'signale' => 'boolean',
        ];
    }

    public function temoignage(): BelongsTo
    {
        return $this->belongsTo(Temoignage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
