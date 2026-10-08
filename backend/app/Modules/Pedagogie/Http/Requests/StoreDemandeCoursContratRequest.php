<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Création du contrat de cours depuis une demande.
 *
 * Le contrat est un engagement de facturation : ce formulaire reprend donc les
 * règles de `StoreContratCoursRequest` (taux entier, heures fractionnaires,
 * au moins une affectation).
 *
 * Deux différences :
 *
 *  - `eleve_id` n'est **pas** un champ. Il vient de `demande_cours.eleve_id`,
 *    renseigné par l'étape précédente ; le laisser saisissable permettrait
 *    d'attacher le contrat d'une famille à l'élève d'une autre.
 *  - `type_cours_id` reste obligatoire alors que le service sait déjà retomber
 *    sur le type demandé : l'admin doit confirmer le mode de cours avant qu'un
 *    engagement de facturation n'existe.
 */
class StoreDemandeCoursContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Roles::estAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'type_cours_id' => ['required', 'integer', 'exists:type_cours,id'],

            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],

            'autres_frais_suivi' => ['nullable', 'numeric', 'min:0'],
            'notes_admin' => ['nullable', 'string', 'max:2000'],

            'affectations' => ['required', 'array', 'min:1'],
            'affectations.*.enseignant_id' => ['required', 'integer', 'exists:enseignant_profils,id'],
            'affectations.*.matiere_id' => ['required', 'integer', 'exists:matieres,id'],
            // Entier : les montants du projet sont stockés en entier (FCFA) et
            // `FacturationService` fait `(int)` sur ce champ.
            'affectations.*.taux_horaire_enseignant' => ['required', 'integer', 'min:0'],
            // `decimal(5,2)` : 1,5 h par affectation est une valeur legitimate.
            'affectations.*.nombre_heures_prevues' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'affectations.*.date_affectation' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_cours_id.required' => 'Confirmez le mode de cours avant de créer le contrat.',
            'affectations.required' => 'Un contrat doit comporter au moins une affectation enseignant.',
            'affectations.*.nombre_heures_prevues.numeric' => 'Le nombre d\'heures prévues doit être un nombre.',
            'affectations.*.nombre_heures_prevues.max' => 'Le nombre d\'heures prévues ne peut pas dépasser 999,99.',
            'affectations.*.taux_horaire_enseignant.integer' => 'Le taux horaire doit être un nombre entier de FCFA.',
        ];
    }
}