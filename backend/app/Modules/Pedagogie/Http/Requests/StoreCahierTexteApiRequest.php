<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Saisie d'une séance via l'API (T7A.5).
 *
 * Différence assumée avec `StoreCahierTexteRequest` (web) : la date peut être
 * **antérieure à aujourd'hui**. Un cahier de texte se saisit le jour même, mais
 * l'enseignant oublie, ou l'application était hors ligne — refuser la veille
 * rendait impossible toute ratification le lendemain et la séance demeurait
 * définitivement absente des heures payées. La borne basse reste la période
 * comptable ouverte, contrôlée par `GardePeriodeOuverte` côté service : on peut
 * rattraper, jamais réécrire une période close.
 *
 * La borne haute reste en revanche interdite : saisir une séance à venir
 * produirait des heures qui n'ont pas eu lieu.
 */
class StoreCahierTexteApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('enseignant') === true
            && $this->user()?->enseignantProfil !== null;
    }

    public function rules(): array
    {
        return [
            'affectation_enseignant_id' => [
                'required',
                'integer',
                'exists:affectation_enseignants,id',
            ],

            // Clé d'idempotence hors-ligne : le client qui n'a pas reçu l'accusé
            // de réception réémet le même POST et doit obtenir la séance déjà
            // créée, pas une seconde ligne dont les heures seraient comptées
            // deux fois en facture et en paie.
            'uuid_client' => [
                'nullable',
                'uuid',
            ],

            'date_seance' => [
                'required',
                'date',
                'before_or_equal:today',
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
            'affectation_enseignant_id.required' => 'Veuillez choisir le cours concerné.',
            'affectation_enseignant_id.exists' => 'Le cours sélectionné est invalide.',
            'uuid_client.uuid' => 'L\'identifiant de séance est invalide.',
            'date_seance.required' => 'La date de la séance est obligatoire.',
            'date_seance.before_or_equal' => 'Une séance ne peut pas être saisie à une date future.',
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