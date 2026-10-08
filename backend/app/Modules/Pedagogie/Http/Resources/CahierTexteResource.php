<?php

namespace App\Modules\Pedagogie\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une séance du cahier de texte (T7A.5).
 *
 * La séance est présentée rattachée à son contexte complet — élève, matière,
 * enseignant — pour la même raison que le créneau de planning : « Mardi 18h,
 * Maths, Mme Traoré » se lit, une ligne de contenu seule ne s'explique pas.
 *
 * `uuid_client` est exposé même dans les listes : c'est la clé de
 * synchronisation hors-ligne. Sans elle, l'application ne peut pas distinguer
 * « séance que je viens d'envoyer » de « séance déjà enregistrée ailleurs ».
 */
class CahierTexteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $affectation = $this->affectation;
        $eleve = $affectation?->contrat?->eleve;

        return [
            'id' => $this->id,
            'uuid_client' => $this->uuid_client,
            'affectation_enseignant_id' => $this->affectation_enseignant_id,
            'contrat_cours_id' => $affectation?->contrat_cours_id,

            'date_seance' => $this->date_seance?->format('Y-m-d'),
            'heure_debut' => substr((string) $this->heure_debut, 0, 5),
            'heure_fin' => substr((string) $this->heure_fin, 0, 5),
            'duree_heures' => (float) $this->duree_heures,

            'contenu_cours' => $this->contenu_cours,
            'objectifs_atteints' => $this->objectifs_atteints,
            'observations' => $this->observations,

            'matiere' => $affectation?->matiere ? [
                'id' => $affectation->matiere->id,
                'nom' => $affectation->matiere->nom,
                'sigle' => $affectation->matiere->sigle,
            ] : null,

            'enseignant' => $affectation?->enseignant?->user ? [
                'id' => $affectation->enseignant_id,
                'nom' => $affectation->enseignant->user->nom,
                'prenom' => $affectation->enseignant->user->prenom,
            ] : null,

            'eleve' => $eleve ? [
                'id' => $eleve->id,
                'nom' => $eleve->user?->nom,
                'prenom' => $eleve->user?->prenom,
            ] : null,
        ];
    }
}