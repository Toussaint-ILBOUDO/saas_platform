<?php

namespace App\Modules\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'canal' => 'required|string|in:whatsapp,facebook,linkedin,copier',
        ];
    }

    public function messages(): array
    {
        return [
            'canal.required' => 'Le canal de partage est obligatoire.',
            'canal.in' => 'Ce canal de partage n\'est pas valide.',
        ];
    }
}
