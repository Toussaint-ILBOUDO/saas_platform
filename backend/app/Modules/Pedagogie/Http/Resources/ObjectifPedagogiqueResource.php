<?php

namespace App\Modules\Pedagogie\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un objectif pédagogique (T7A.6, D-050).
 *
 * L'objectif est présenté avec son triplet d'identité complet — élève, période
 * comptable, enseignant — car c'est ce triplet qui définit la ligne ; sans lui,
 * « moyenne visée 14 » ne dit ni pour qui, ni pour quelle période. La
 * ventilation par matière partage le même sort : chaque ligne garde sa propre
 * moyenne visée et obtenue.
 */
class ObjectifPedagogiqueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $periode = $this->periode;
        $eleve = $this->eleve;

        return [
            'id' => $this->id,

            'eleve' => $eleve ? [
                'id' => $eleve->id,
                'nom' => $eleve->user?->nom,
                'prenom' => $eleve->user?->prenom,
                'classe' => $eleve->classe?->nom,
            ] : null,

            'periode' => $periode ? [
                'id' => $periode->id,
                'label' => $periode->label,
                'date_debut' => $periode->date_debut?->format('Y-m-d'),
                'date_fin' => $periode->date_fin?->format('Y-m-d'),
                'statut' => $periode->statut,
                'est_ouverte' => $periode->estOuverte(),
            ] : null,

            'enseignant' => $this->enseignant ? [
                'id' => $this->enseignant_id,
                'nom' => $this->enseignant->user?->nom,
                'prenom' => $this->enseignant->user?->prenom,
            ] : null,

            'moyenne_visee' => $this->moyenne_visee !== null
                ? (float) $this->moyenne_visee
                : null,
            'moyenne_obtenue' => $this->moyenne_obtenue !== null
                ? (float) $this->moyenne_obtenue
                : null,
            'materiel_disponible' => $this->materiel_disponible,
            'materiel_manquant' => $this->materiel_manquant,
            'commentaire_admin' => $this->commentaire_admin,

            'matieres' => $this->objectifsMatieres
                ->map(fn ($ligne) => [
                    'id' => $ligne->id,
                    'matiere' => $ligne->matiere ? [
                        'id' => $ligne->matiere->id,
                        'nom' => $ligne->matiere->nom,
                        'sigle' => $ligne->matiere->sigle,
                    ] : null,
                    'moyenne_visee' => $ligne->moyenne_visee !== null
                        ? (float) $ligne->moyenne_visee
                        : null,
                    'moyenne_obtenue' => $ligne->moyenne_obtenue !== null
                        ? (float) $ligne->moyenne_obtenue
                        : null,
                    'commentaire' => $ligne->commentaire,
                ])
                ->values(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
