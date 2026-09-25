<?php

namespace App\Modules\Auth\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RoleActifApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Le rôle actif est obligatoire.',
        ];
    }
}