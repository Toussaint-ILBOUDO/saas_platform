<?php

namespace App\Modules\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFaqSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('faq_sections', 'slug')->ignore($this->route('section')),
            ],
            'description' => 'nullable|string|max:500',
            'order_index' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre de la section est obligatoire.',
            'title.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'slug.unique' => 'Ce slug est déjà utilisé par une autre section.',
            'description.max' => 'La description ne doit pas dépasser 500 caractères.',
        ];
    }
}
