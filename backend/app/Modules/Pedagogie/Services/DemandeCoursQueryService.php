<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\DemandeCours;
use App\Support\Recherche;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Lecture des demandes de cours côté administration.
 *
 * Séparée de `DemandeCoursAdminService` (qui écrit) pour que la liste puisse
 * être filtrée sans charger la logique de traitement.
 */
class DemandeCoursQueryService
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return DemandeCours::query()
            ->with(['classe', 'typeCours'])
            ->when(
                $filters['search'] ?? null,
                // La recherche porte sur le parent : c'est par son nom qu'on
                // retrouve une demande, ou par son téléphone pour rappeler —
                // WhatsApp compris, d'où les deux colonnes.
                fn ($query, $search) => Recherche::likeInsensible(
                    $query,
                    ['nom_parent', 'prenom_parent', 'telephone', 'telephone_whatsapp'],
                    $search
                )
            )
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($query) => $query->where('statut', $filters['statut'])
            )
            ->when(
                $filters['classe_id'] ?? null,
                fn ($query, $id) => $query->where('classe_id', $id)
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function show(DemandeCours $demandeCours): DemandeCours
    {
        return $demandeCours->load([
            'classe',
            'typeCours',
            'matieres',
            // Le dossier créé (parent / élève / contrat) fait partie de la fiche :
            // sans lui, la liste des actions disponibles serait trompeuse.
            'parent',
            'eleve.classe',
            'contratCours',
        ]);
    }

    /**
     * Compteurs des cartes de tête. L'en attente est calculé en base et non
     * déduit du total affiché : c'est la seule valeur qui déclenche une action.
     *
     * `traitees` et `annulees` couvre l'ensemble du cabinet : leurs somme avec
     * `en_attente` redonne `total`, ce qui permet à l'écran de vérifier sa
     * propre arithmétique au lieu d'afficher trois compteurs sans lien.
     */
    public function stats(): array
    {
        return [
            'total' => DemandeCours::count(),
            'en_attente' => DemandeCours::where('statut', DemandeCours::EN_ATTENTE)->count(),
            'traitees' => DemandeCours::where('statut', DemandeCours::TRAITEE)->count(),
            'annulees' => DemandeCours::where('statut', DemandeCours::ANNULEE)->count(),
            // Une demande dont le contrat existe n'est plus « à qualifier ».
            'avec_contrat' => DemandeCours::whereNotNull('contrat_cours_id')->count(),
        ];
    }
}