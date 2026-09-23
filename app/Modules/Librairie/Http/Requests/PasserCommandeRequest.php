<?php

namespace App\Modules\Librairie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasserCommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('panier') && is_string($this->input('panier'))) {
            $decoded = json_decode($this->input('panier'), true);
            $this->merge([
                'panier' => is_array($decoded) ? $decoded : [],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nom_client' => ['required', 'string', 'max:255'],
            'telephone_client' => ['required', 'string', 'max:20'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'adresse_livraison' => ['required', 'string', 'max:500'],
            'quartier' => ['nullable', 'string', 'max:255'],
            'is_livraison' => ['nullable', 'boolean'],
            'frais_livraison' => ['nullable', 'numeric', 'min:0'],
            'mode_paiement' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'panier' => ['required', 'array', 'min:1'],
            'panier.*.produit_id' => ['required', 'exists:produits,id'],
            'panier.*.quantite' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_client.required' => 'Votre nom est obligatoire.',
            'telephone_client.required' => 'Votre numéro de téléphone est obligatoire.',
            'whatsapp.required' => 'Votre numéro WhatsApp est obligatoire.',
            'adresse_livraison.required' => 'Votre adresse de livraison est obligatoire.',
            'panier.required' => 'Votre panier est vide.',
            'panier.min' => 'Votre panier doit contenir au moins un article.',
            'panier.*.produit_id.required' => 'Le produit est obligatoire.',
            'panier.*.produit_id.exists' => 'Le produit sélectionné est invalide.',
            'panier.*.quantite.required' => 'La quantité est obligatoire.',
            'panier.*.quantite.integer' => 'La quantité doit être un nombre entier.',
            'panier.*.quantite.min' => 'La quantité minimale est 1.',
        ];
    }
}
