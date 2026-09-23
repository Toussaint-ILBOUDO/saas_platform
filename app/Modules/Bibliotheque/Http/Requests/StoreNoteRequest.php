<?php

namespace App\Modules\Bibliotheque\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => 'required|integer|min:1|max:5',
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'La note est obligatoire.',
            'note.min' => 'La note minimum est 1.',
            'note.max' => 'La note maximum est 5.',
        ];
    }
}
