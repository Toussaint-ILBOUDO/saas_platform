<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeAjustementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeId = $this->route('typeAjustement')?->id;

        return [
            'libelle'   => 'required|string|max:255|unique:type_ajustements,libelle,' . $typeId,
            'direction' => 'required|in:credit,debit',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
