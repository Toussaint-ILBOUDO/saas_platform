<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Models\RapportElement;
use App\Models\RapportSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Réordonnancement du modèle de rapport.
 *
 * Le corps est une simple liste d'ids (`ids`) dans le nouvel ordre voulu, que
 * la route associe aux sections ou aux éléments selon son usage. La règle
 * s'applique au modèle visé déclaré dans `$modele`.
 */
class ReordonnerRapportModeleRequest extends FormRequest
{
    protected string $modele = RapportSection::class;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:2'],
            'ids.*' => ['integer', Rule::exists($this->table(), 'id')],
        ];
    }

    protected function table(): string
    {
        return (new ($this->modele)())->getTable();
    }
}