<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole([
            'admin',
            'super-admin'
        ]);
    }

    public function rules(): array
    {
        $classe = $this->route('classe');

        return [

            'nom' => [
                'required',
                'string',
                'max:255'
            ],

            'sigle' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classes', 'sigle')
                    ->ignore($classe->id)
            ]
        ];
    }
}