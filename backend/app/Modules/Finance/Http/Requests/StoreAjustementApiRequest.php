<?php

namespace App\Modules\Finance\Http\Requests;

use App\Models\BulletinPaie;
use App\Models\TypeAjustement;
use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.9 — Ajout d'un ajustement (prime ou retenue) à un bulletin (API admin).
 *
 * Le type référence un `TypeAjustement` actif qui porte la direction
 * (crédit/débit). Un bulletin déjà versé ne se modifie plus — le service le
 * refuse.
 */
class StoreAjustementApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bulletin'));
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