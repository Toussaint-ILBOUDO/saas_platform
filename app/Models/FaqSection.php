<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FaqSection extends Model
{
    use SoftDeletes;

    protected $table = 'faq_sections';

    protected $fillable = [
        'cabinet_id',
        'title',
        'slug',
        'description',
        'order_index',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FaqQuestion::class)->orderBy('order_index');
    }

    public function activeQuestions(): HasMany
    {
        return $this->hasMany(FaqQuestion::class)
            ->where('is_active', true)
            ->orderBy('order_index');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order_index');
    }

    protected static function booted(): void
    {
        static::saving(function (FaqSection $section) {
            if (empty($section->slug)) {
                $section->slug = Str::slug($section->title);
            }
        });
    }
}
