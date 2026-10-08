<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Création de l'élève depuis une demande de cours.
 *
 * Le parent n'est **pas** un champ de ce formulaire : il vient de
 * `demande_cours.parent_id`, renseigné par l'étape précédente. Le laisser
 * saisissable ouvrirait la porte à rattacher l'élève d'une famille au parent
 * d'une autre — le service rejette ce cas, mais autant ne pas proposer l'erreur.
 *
 * Différence notable avec le formulaire Blade : l'élève porte un **prénom** que
 * la demande ne contient pas. La demande vient d'un parent qui demande des
 * cours pour « sa fille » sans avoir donné son prénom — d'où le champ requis
 * ici, là où `EleveController::create` le laisse vide pour un import.
 */
class StoreDemandeCoursEleveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Roles::estAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:users,id'],
            'prenom' => ['required', 'string', 'max:255'],
            'nom' => ['nullable', 'string', 'max:255'],

            // Repli sur la classe demandée par le parent.
            'classe_id' => ['nullable', 'integer', 'exists:classes,id'],

            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'ecole' => ['nullable', 'string', 'max:255'],

            // Mot de passe : facultatif, car `EleveService::create` en génère un
            // provisoire. Le compte reste désactivé (`statut = false`) et
            // l'activation passe par `EleveController::accountForm`.
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'prenom.required' => 'Le prénom de l\'élève est obligatoire : la demande ne le mentionne pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}