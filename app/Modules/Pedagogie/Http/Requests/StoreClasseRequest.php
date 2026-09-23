<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClasseRequest extends FormRequest
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
                'unique:classes,sigle'
            ]
        ];
    }
}