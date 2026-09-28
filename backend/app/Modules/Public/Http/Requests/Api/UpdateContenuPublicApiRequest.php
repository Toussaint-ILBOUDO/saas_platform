<?php

namespace App\Modules\Public\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContenuPublicApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme' => 'nullable|array',
            'footer' => 'nullable|array',
            'data' => 'nullable|array',
            // Fiche cabinet (D-044) : données structurées du site public.
            'data.fiche' => 'nullable|array',
            'data.fiche.identite' => 'nullable|array',
            'data.fiche.contact' => 'nullable|array',
            'data.fiche.paiements' => 'nullable|array',
            'data.fiche.zones' => 'nullable|array',
            'data.fiche.zones.localites' => 'nullable|array',
            'data.fiche.reseaux' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'theme.array' => 'Le thème doit être un objet JSON.',
            'footer.array' => 'Le pied de page doit être un objet JSON.',
            'data.array' => 'Les données publiques doivent être un objet JSON.',
            'data.fiche.array' => 'La fiche cabinet doit être un objet JSON.',
            'data.fiche.identite.array' => 'L\'identité de la fiche doit être un objet JSON.',
            'data.fiche.contact.array' => 'Le contact de la fiche doit être un objet JSON.',
            'data.fiche.paiements.array' => 'Les paiements de la fiche doivent être un objet JSON.',
            'data.fiche.zones.array' => 'Les zones de la fiche doivent être un objet JSON.',
            'data.fiche.zones.localites.array' => 'Les localités doivent être un tableau.',
            'data.fiche.reseaux.array' => 'Les réseaux de la fiche doivent être un objet JSON.',
        ];
    }
}