<?php

namespace App\Modules\Communication\Services;

use App\Models\FaqQuestion;
use App\Models\FaqSection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FaqService
{
    protected const CACHE_KEY = 'faq.public';

    /*
    |--------------------------------------------------------------------------
    | Sections
    |--------------------------------------------------------------------------
    */

    public function listSections(int $perPage = 15)
    {
        return FaqSection::query()
            ->withCount('questions')
            ->orderBy('order_index')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function createSection(array $data): FaqSection
    {
        return DB::transaction(function () use ($data) {
            $section = FaqSection::create([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug(! empty($data['slug']) ? $data['slug'] : $data['title']),
                'description' => $data['description'] ?? null,
                'order_index' => $data['order_index'] ?? $this->nextOrderIndex(FaqSection::class),
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->flushCache();

            return $section;
        });
    }

    public function updateSection(FaqSection $section, array $data): FaqSection
    {
        return DB::transaction(function () use ($section, $data) {
            $section->update([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug(
                    ! empty($data['slug']) ? $data['slug'] : $data['title'],
                    $section->id
                ),
                'description' => $data['description'] ?? null,
                'order_index' => $data['order_index'] ?? $section->order_index,
                'is_active' => $data['is_active'] ?? $section->is_active,
            ]);

            $this->flushCache();

            return $section->fresh();
        });
    }

    public function deleteSection(FaqSection $section): bool
    {
        return DB::transaction(function () use ($section) {
            $deleted = $section->delete();

            $this->flushCache();

            return $deleted;
        });
    }

    public function toggleSection(FaqSection $section): FaqSection
    {
        return DB::transaction(function () use ($section) {
            $section->update([
                'is_active' => ! $section->is_active,
            ]);

            $this->flushCache();

            return $section->fresh();
        });
    }

    public function reorderSections(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                FaqSection::whereKey($id)->update(['order_index' => $index]);
            }

            $this->flushCache();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Questions
    |--------------------------------------------------------------------------
    */

    public function listQuestions(FaqSection $section, int $perPage = 15)
    {
        return FaqQuestion::query()
            ->where('faq_section_id', $section->id)
            ->orderBy('order_index')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function createQuestion(array $data): FaqQuestion
    {
        return DB::transaction(function () use ($data) {
            $question = FaqQuestion::create([
                'faq_section_id' => $data['faq_section_id'],
                'question' => $data['question'],
                'answer' => $data['answer'],
                'order_index' => $data['order_index'] ?? $this->nextOrderIndex(
                    FaqQuestion::class,
                    'faq_section_id',
                    $data['faq_section_id']
                ),
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->flushCache();

            return $question;
        });
    }

    public function updateQuestion(FaqQuestion $question, array $data): FaqQuestion
    {
        return DB::transaction(function () use ($question, $data) {
            $question->update([
                'faq_section_id' => $data['faq_section_id'],
                'question' => $data['question'],
                'answer' => $data['answer'],
                'order_index' => $data['order_index'] ?? $question->order_index,
                'is_active' => $data['is_active'] ?? $question->is_active,
            ]);

            $this->flushCache();

            return $question->fresh();
        });
    }

    public function deleteQuestion(FaqQuestion $question): bool
    {
        return DB::transaction(function () use ($question) {
            $deleted = $question->delete();

            $this->flushCache();

            return $deleted;
        });
    }

    public function toggleQuestion(FaqQuestion $question): FaqQuestion
    {
        return DB::transaction(function () use ($question) {
            $question->update([
                'is_active' => ! $question->is_active,
            ]);

            $this->flushCache();

            return $question->fresh();
        });
    }

    public function reorderQuestions(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                FaqQuestion::whereKey($id)->update(['order_index' => $index]);
            }

            $this->flushCache();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */

    public function getActiveSectionsWithQuestions()
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return FaqSection::query()
                ->with([
                    'questions' => fn ($query) => $query
                        ->where('is_active', true)
                        ->orderBy('order_index'),
                ])
                ->where('is_active', true)
                ->orderBy('order_index')
                ->orderBy('id')
                ->get()
                ->reject(fn (FaqSection $section) => $section->questions->isEmpty())
                ->values();
        });
    }

    public function getActiveQuestionsCount(): int
    {
        return FaqQuestion::query()
            ->where('is_active', true)
            ->whereHas('section', fn ($query) => $query->where('is_active', true))
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $suffix = 2;

        while (FaqSection::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $original.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function nextOrderIndex(
        string $model,
        ?string $column = null,
        mixed $value = null
    ): int {
        $query = $model::query();

        if ($column !== null && $value !== null) {
            $query->where($column, $value);
        }

        return (int) $query->max('order_index') + 1;
    }

    protected function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
