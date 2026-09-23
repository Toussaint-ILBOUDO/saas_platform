<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['nom', 'slug', 'usage_count'];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->nom);
            }
        });
    }

    public function documents()
    {
        return $this->belongsToMany(DocumentBibliotheque::class, 'document_tags')
            ->withTimestamps();
    }
}
