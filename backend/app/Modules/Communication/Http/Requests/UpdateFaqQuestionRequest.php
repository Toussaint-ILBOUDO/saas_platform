<?php

namespace App\Modules\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFaqQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'faq_section_id' => 'required|exists:faq_sections,id',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'order_index' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'faq_section_id.required' => 'La section est obligatoire.',
            'faq_section_id.exists' => 'La section sélectionnée n\'existe plus.',
            'question.required' => 'La question est obligatoire.',
            'question.max' => 'La question ne doit pas dépasser 500 caractères.',
            'answer.required' => 'La réponse est obligatoire.',
        ];
    }
}
