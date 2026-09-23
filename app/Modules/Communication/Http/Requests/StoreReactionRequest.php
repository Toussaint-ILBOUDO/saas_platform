<?php

namespace App\Modules\Communication\Http\Requests;

use App\Modules\Communication\Enums\ActualiteReaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reaction' => 'required|string|in:'.implode(',', ActualiteReaction::VALIDES),
        ];
    }

    public function messages(): array
    {
        return [
            'reaction.required' => 'La réaction est obligatoire.',
            'reaction.in' => 'Cette réaction n\'est pas valide.',
        ];
    }
}
