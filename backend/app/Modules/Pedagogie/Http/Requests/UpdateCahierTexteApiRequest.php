<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Correction d'une séance via l'API (T7A.5).
 *
 * `date_seance` est acceptée mais volontairement **immuable** : le service
 * compare la valeur reçue à celle de la séance et refuse tout écart. La
 * recevoir ici plutôt que de l'ignorer permet au client de renvoyer sa copie
 * locale entière sans avoir à savoir ce qu'il peut omettre ; l'utilisateur
 * reçoit alors un message explicite au lieu d'un champ silencieusement
 * ignoré.
 *
 * `affectation_enseignant_id` n'est pas repris : changer de cours ferait
 * porter les heures d'une séance à une autre ligne de rapport (cf. D-054 sur
 * l'immuabilité de la matière d'une affectation).
 */
class UpdateCahierTexteApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('enseignant') === true
            && $this->user()?->enseignantProfil !== null;
    }

    public function rules(): array
    {
        return [
            'date_seance' => [
                'nullable',
                'date',
            ],

            'heure_debut' => [
                'required',
                'date_format:H:i',
            ],

            'heure_fin' => [
                'required',
                'date_format:H:i',
                'after:heure_debut',
            ],

            'contenu_cours' => [
                'required',
                'string',
                'min:10',
            ],

            'objectifs_atteints' => [
                'nullable',
                'string',
            ],

            'observations' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'heure_debut.required' => 'L\'heure de début est obligatoire.',
            'heure_debut.date_format' => 'L\'heure de début doit être au format HH:MM.',
            'heure_fin.required' => 'L\'heure de fin est obligatoire.',
            'heure_fin.date_format' => 'L\'heure de fin doit être au format HH:MM.',
            'heure_fin.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'contenu_cours.required' => 'Le contenu du cours est obligatoire.',
            'contenu_cours.min' => 'Décrivez le cours en quelques mots (10 caractères minimum).',
        ];
    }
}