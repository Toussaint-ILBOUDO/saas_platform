<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandeCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_parent' => [
                'required',
                'string',
                'max:255'
            ],

            'prenom_parent' => [
                'required',
                'string',
                'max:255'
            ],

            'telephone' => [
                'required',
                'string',
                'max:20'
            ],

            // Optionnel : le parent peut n'avoir qu'un fixe. L'administration
            // retombe alors sur `telephone` pour le contact WhatsApp.
            'telephone_whatsapp' => [
                'nullable',
                'string',
                'max:20'
            ],

            'type_cours_id' => [
                'required',
                'exists:type_cours,id'
            ],

            'classe_id' => [
                'required',
                'exists:classes,id'
            ],

            'volume_horaire_estime' => [
                'required',
                'integer',
                'min:1'
            ],

            'matieres' => [
                'required',
                'array',
                'min:1'
            ],

            'matieres.*' => [
                'exists:matieres,id'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_parent.required' => 'Le nom du parent est obligatoire.',
            'prenom_parent.required' => 'Le prénom du parent est obligatoire.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'telephone_whatsapp.max' => 'Le numéro WhatsApp est trop long.',

            'type_cours_id.required' => 'Le type de cours est obligatoire.',
            'type_cours_id.exists' => 'Le type de cours sélectionné est invalide.',

            'classe_id.required' => 'La classe est obligatoire.',
            'classe_id.exists' => 'La classe sélectionnée est invalide.',

            'volume_horaire_estime.required' => 'Le volume horaire est obligatoire.',
            'volume_horaire_estime.integer' => 'Le volume horaire doit être un nombre entier.',

            'matieres.required' => 'Veuillez sélectionner au moins une matière.',
            'matieres.array' => 'Les matières doivent être envoyées sous forme de liste.',
            'matieres.min' => 'Veuillez sélectionner au moins une matière.',

            'matieres.*.exists' => 'Une matière sélectionnée est invalide.',
        ];
    }
}