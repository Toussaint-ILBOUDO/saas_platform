<?php

namespace App\Modules\Communication\Http\Requests;

use App\Modules\Communication\Enums\ActualiteCanal;
use App\Modules\Communication\Enums\ActualiteDestinataire;
use Illuminate\Foundation\Http\FormRequest;

class PublishActualiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destinataires' => 'nullable|array',
            'destinataires.*' => 'in:'.implode(',', ActualiteDestinataire::VALIDES),
            'canal' => 'required_with:destinataires|in:'.implode(',', ActualiteCanal::VALIDES),
        ];
    }

    public function messages(): array
    {
        return [
            'destinataires.*.in' => 'Le destinataire sélectionné n\'est pas valide.',
            'canal.required_with' => 'Le canal de diffusion est obligatoire lorsque des destinataires sont sélectionnés.',
            'canal.in' => 'Le canal de diffusion sélectionné n\'est pas valide.',
        ];
    }
}
