<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqSectionAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $questionCount = $this->questions_count ?? $this->questions()->whereNull('deleted_at')->count();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'order_index' => $this->order_index,
            'is_active' => $this->is_active,
            'questions_count' => $questionCount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}