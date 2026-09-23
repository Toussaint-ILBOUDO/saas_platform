<?php

namespace App\Modules\Librairie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProduitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categorie_id' => ['sometimes', 'required', 'exists:categorie_produits,id'],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'prix' => ['sometimes', 'required', 'numeric', 'min:0'],
            'frais_livraison' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'categorie_id.required' => 'La catégorie est obligatoire.',
            'categorie_id.exists' => 'La catégorie sélectionnée est invalide.',
            'nom.required' => 'Le nom du produit est obligatoire.',
            'nom.max' => 'Le nom ne peut dépasser 255 caractères.',
            'description.max' => 'La description ne peut dépasser 5000 caractères.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix ne peut être négatif.',
            'frais_livraison.numeric' => 'Les frais de livraison doivent être un nombre.',
            'frais_livraison.min' => 'Les frais de livraison ne peuvent être négatifs.',
            'image.image' => 'Le fichier doit être une image.',
            'image.max' => 'L\'image ne peut dépasser 5 Mo.',
        ];
    }
}
