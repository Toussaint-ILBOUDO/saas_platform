<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCahierTexteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->hasRole('enseignant');
    }

    public function rules(): array
    {
        return [

            'heure_debut' => [
                'required',
                'date_format:H:i',
            ],

            'heure_fin' => [
                'required',
                'date_format:H:i',
                'after:heure_debut',
            ],

            'contenu_cours' => [
                'required',
                'string',
                'min:10',
            ],

            'objectifs_atteints' => [
                'nullable',
                'string',
            ],

            'observations' => [
                'nullable',
                'string',
            ],
        ];
    }
}
