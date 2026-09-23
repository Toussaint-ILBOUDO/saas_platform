<?php

namespace App\Modules\Librairie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterProduitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'categorie_id' => ['nullable', 'integer', 'exists:categorie_produits,id'],
            'prix_min' => ['nullable', 'numeric', 'min:0'],
            'prix_max' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'string', 'in:recent,prix_asc,prix_desc,nom'],
        ];
    }
}
