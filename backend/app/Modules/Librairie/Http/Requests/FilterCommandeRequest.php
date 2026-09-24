<?php

namespace App\Modules\Librairie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterCommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'statut' => ['nullable', 'string', 'in:en_attente,confirmee,en_preparation,livree,annulee'],
        ];
    }
}
