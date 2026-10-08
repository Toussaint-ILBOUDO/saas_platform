<?php

namespace App\Modules\Finance\Http\Requests;

use App\Models\BulletinPaie;
use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.9 — Correction administrative d'un bulletin contesté.
 *
 * Le commentaire admin est recopié dans la ligne « commentaire_enseignant »
 * (le behavior legacy), le service rejette tout bulletin non contesté.
 */
class CorrigerBulletinApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bulletin'));
    }

    public function rules(): array
    {
        return [
            'commentaire_admin' => ['nullable', 'string', 'max:1000'],
        ];
    }
}