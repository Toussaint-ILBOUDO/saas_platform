<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRapportMensuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }


    public function rules(): array
    {
        return [

            'contrat_cours_id' => [
                'required',
                'exists:contrat_cours,id'
            ],

            'periode_id' => [
                'required',
                'exists:periode_comptables,id'
            ],


            'point_notes_matieres' => [
                'nullable',
                'string',
                'max:5000'
            ],

            'point_notes_autres_matieres' => [
                'nullable',
                'string',
                'max:5000'
            ],


            'difficultes_rencontrees' => [
                'nullable',
                'string',
                'max:5000'
            ],


            'solutions_trouvees' => [
                'nullable',
                'string',
                'max:5000'
            ],


            'attentes_parents_eleve' => [
                'nullable',
                'string',
                'max:5000'
            ],


            'attentes_administration' => [
                'nullable',
                'string',
                'max:5000'
            ],


            'appreciation_evolution' => [
                'nullable',
                'string',
                'max:5000'
            ],


            'observations' => [
                'nullable',
                'string',
                'max:5000'
            ],

        ];
    }
}