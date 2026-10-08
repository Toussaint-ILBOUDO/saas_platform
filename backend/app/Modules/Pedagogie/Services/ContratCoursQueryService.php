<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\ContratCours;
use App\Support\Recherche;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContratCoursQueryService
{
    /**
     * Liste d'administration. Les filtres existent pour une raison concrète :
     * un cabinet actif compte des centaines de contrats, et « le contrat
     * suspendu de Karima » n'est pas trouvable dans une liste non filtrée.
     */
    public function paginate(
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return ContratCours::query()
            ->with([
                'eleve.user',
                'eleve.classe',
                'typeCours',
                'affectations.enseignant.user',
                'affectations.matiere',
            ])
            ->when(
                $filters['search'] ?? null,
                // La recherche porte sur l'élève : c'est par son nom qu'on
                // retrouve un contrat, pas par un identifiant.
                fn ($query, $search) => $query->whereHas(
                    'eleve.user',
                    fn ($q) => Recherche::likeInsensible($q, ['nom', 'prenom'], $search)
                )
            )
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($query) => $query->where('statut', $filters['statut'])
            )
            ->when(
                $filters['type_cours_id'] ?? null,
                fn ($query, $id) => $query->where('type_cours_id', $id)
            )
            ->withCount('affectations')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForEnseignant(
        int $enseignantId,
        int $perPage = 20
    ): LengthAwarePaginator {
        return ContratCours::query()
            ->with([
                'eleve.user',
                'typeCours',
                'affectations' => function ($query) use ($enseignantId) {
                    $query->where('enseignant_id', $enseignantId)
                        ->with(['matiere', 'enseignant.user']);
                },
            ])
            ->whereHas('affectations', function ($query) use ($enseignantId) {
                $query->where('enseignant_id', $enseignantId);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForEleve(
        int $eleveId,
        int $perPage = 20
    ): LengthAwarePaginator {
        return ContratCours::query()
            ->with([
                'eleve.user',
                'typeCours',
                'affectations.enseignant' => fn ($q) => $q->with('user'),
                'affectations.matiere',
            ])
            ->where('eleve_id', $eleveId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Contrats des enfants du parent connecté (T7A.3 — portail parent).
     *
     * Le périmètre est déduit du connecté, jamais d'un paramètre de requête :
     * `eleves.parent_id` référence `users.id`, ce qui est exactement la clé
     * utilisée par `ContratCoursPolicy::view` pour autoriser la consultation.
     * Un parent ne peut donc pas fabriquer la liste d'une autre famille.
     */
    public function paginateForParent(
        int $parentId,
        int $perPage = 20
    ): LengthAwarePaginator {
        return ContratCours::query()
            ->with([
                'eleve.user',
                'eleve.classe',
                'typeCours',
                'affectations.enseignant.user',
                'affectations.matiere',
            ])
            ->whereHas('eleve', fn ($query) => $query->where('parent_id', $parentId))
            ->withCount('affectations')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function show(
        ContratCours $contrat
    ): ContratCours {
        return $contrat->load([
            'eleve.user',
            'eleve.classe',
            'typeCours',
            'affectations.enseignant.user',
            'affectations.matiere',
        ])->loadCount('affectations');
    }
}