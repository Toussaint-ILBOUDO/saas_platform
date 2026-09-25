<?php

namespace App\Modules\Public\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContenuPublicApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme' => 'nullable|array',
            'footer' => 'nullable|array',
            'data' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'theme.array' => 'Le thème doit être un objet JSON.',
            'footer.array' => 'Le pied de page doit être un objet JSON.',
            'data.array' => 'Les données publiques doivent être un objet JSON.',
        ];
    }
}