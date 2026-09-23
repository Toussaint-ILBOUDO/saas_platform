<?php

namespace App\Modules\Temoignages\Http\Requests;

use App\Modules\Temoignages\Enums\TemoignageReactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemoignageReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reaction' => ['required', Rule::in(TemoignageReactionType::VALIDES)],
        ];
    }

    public function messages(): array
    {
        return [
            'reaction.required' => 'Choisissez une réaction.',
            'reaction.in' => 'Cette réaction n\'est pas valide.',
        ];
    }
}
