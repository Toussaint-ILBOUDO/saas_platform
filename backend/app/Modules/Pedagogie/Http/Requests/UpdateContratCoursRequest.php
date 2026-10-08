<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContratCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('contrat.update');
    }

    public function rules(): array
    {
        return [
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'autres_frais_suivi' => ['nullable', 'numeric', 'min:0'],
            'notes_admin' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}