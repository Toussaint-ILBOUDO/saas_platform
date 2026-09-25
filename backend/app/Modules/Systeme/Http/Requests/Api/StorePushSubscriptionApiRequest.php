<?php

namespace App\Modules\Systeme\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePushSubscriptionApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'endpoint' => 'required|url|max:1024',
            'keys' => 'nullable|array',
            'keys.public_key' => 'nullable|string|max:255',
            'keys.auth_token' => 'nullable|string|max:255',
            'content_encoding' => 'nullable|string|in:aesgcm,aes128gcm',
        ];
    }

    public function messages(): array
    {
        return [
            'endpoint.required' => 'L\'endpoint de souscription est obligatoire.',
            'endpoint.url' => 'L\'endpoint de souscription n\'est pas valide.',
            'endpoint.max' => 'L\'endpoint de souscription est trop long.',
            'content_encoding.in' => 'L\'encodage de contenu doit être aesgcm ou aes128gcm.',
        ];
    }
}