<?php

namespace App\Modules\Librairie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'prenom_client' => ['nullable', 'string', 'max:100'],
            'telephone_client' => ['required', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            // L'adresse n'est exigée qu'en cas de livraison : le retrait sur
            // place n'en a pas besoin.
            'adresse_livraison' => [
                'nullable', 'string', 'max:500',
                Rule::requiredIf(fn () => $this->boolean('is_livraison')),
            ],
            'quartier' => ['nullable', 'string', 'max:255'],
            'point_de_repere' => ['nullable', 'string', 'max:255'],
            'lien_geolocalisation' => ['nullable', 'url', 'max:500'],
            'is_livraison' => ['nullable', 'boolean'],
            'mode_paiement' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'cle_idempotence' => ['nullable', 'string', 'max:64'],
            'panier' => ['required', 'array', 'min:1'],
            'panier.*.produit_id' => ['required', 'exists:produits,id'],
            'panier.*.quantite' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_client.required' => 'Votre nom est obligatoire.',
            'telephone_client.required' => 'Votre numéro de téléphone est obligatoire.',
            'adresse_livraison.required' => 'Votre adresse de livraison est obligatoire.',
            'lien_geolocalisation.url' => 'Le lien de géolocalisation doit être une adresse web valide (https://…).',
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
