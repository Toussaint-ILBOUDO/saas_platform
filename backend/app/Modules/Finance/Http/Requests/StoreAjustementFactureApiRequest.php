<?php

namespace App\Modules\Finance\Http\Requests;

use App\Models\TypeAjustement;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Ajout d'un ajustement (bonus ou débit) à une facture (API admin).
 *
 * Le type référence un `TypeAjustement` actif qui porte la direction
 * (crédit/débit). Une facture déjà payée ne se modifie plus — le service le
 * refuse également.
 */
class StoreAjustementFactureApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('facture'));
    }

    public function rules(): array
    {
        return [
            'type_ajustement_id' => [
                'required',
                'exists:type_ajustements,id',
                function ($attribute, $value, $fail) {
                    $type = TypeAjustement::find($value);
                    if ($type && ! $type->is_active) {
                        $fail('Ce type d\'ajustement n\'est plus actif.');
                    }
                },
            ],
            'libelle' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_ajustement_id.required' => 'Veuillez sélectionner un type d\'ajustement.',
            'type_ajustement_id.exists' => 'Le type sélectionné n\'existe pas.',
        ];
    }
}
