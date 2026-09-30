<?php

namespace App\Modules\Public\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogoCabinetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => 'required|image|mimes:jpeg,png,webp,gif|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'Le fichier logo est obligatoire.',
            'logo.image' => 'Le logo doit être une image.',
            'logo.mimes' => 'Le logo doit être au format JPEG, PNG, WEBP ou GIF.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
        ];
    }
}