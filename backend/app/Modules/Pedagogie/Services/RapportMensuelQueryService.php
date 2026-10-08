<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\RapportMensuelEnseignant;
use App\Support\Recherche;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * T7A.7 — Lecture des rapports mensuels.
 *
 * Les filtres servent une raison concrète : un cabinet actif compte des
 * centaines de rapports, et « le rapport rejeté de Karima » n'est pas
 * trouvable dans une liste non filtrée.
 *
 * Le périmètre n'est jamais déduit d'un identifiant devinable : l'index
 * enseignant est borné à son propre `enseignant_id`, l'index admin est
 * réservé au rôle `admin_cabinet` par la route.
 */
class RapportMensuelQueryService
{
    /**
     * Index de l'administration : tous les rapports du cabinet.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return RapportMensuelEnseignant::query()
            ->with([
                'periode',
                'enseignant.user',
                'contratCours.eleve.user',
                'contratCours.typeCours',
            ])
            ->withSum('lignes', 'nombre_heures')
            ->when(
                $filters['periode_id'] ?? null,
                fn ($query, $id) => $query->where('periode_id', $id)
            )
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($query) => $query->where('statut', $filters['statut'])
            )
            ->when(
                $filters['contrat_cours_id'] ?? null,
                fn ($query, $id) => $query->where('contrat_cours_id', $id)
            )
            ->when(
                $filters['enseignant_id'] ?? null,
                fn ($query, $id) => $query->where('enseignant_id', $id)
            )
            ->when(
                $filters['search'] ?? null,
                // La recherche porte sur l'enseignant ET sur l'élève : dans un
                // cabinet, on cherche le rapport « par qui » autant que
                // « pour qui ».
                fn ($query, $search) => $query->where(function ($q) use ($search) {
                    $q->whereHas(
                        'enseignant.user',
                        fn ($sous) => Recherche::likeInsensible($sous, ['nom', 'prenom'], $search)
                    )->orWhereHas(
                        'contratCours.eleve.user',
                        fn ($sous) => Recherche::likeInsensible($sous, ['nom', 'prenom'], $search)
                    );
                })
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Index de l'enseignant : ses seuls rapports.
     */
    public function paginateForEnseignant(
        int $enseignantId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return RapportMensuelEnseignant::query()
            ->where('enseignant_id', $enseignantId)
            ->with([
                'periode',
                'contratCours.eleve.user',
                'contratCours.typeCours',
            ])
            ->withSum('lignes', 'nombre_heures')
            ->when(
                $filters['periode_id'] ?? null,
                fn ($query, $id) => $query->where('periode_id', $id)
            )
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($query) => $query->where('statut', $filters['statut'])
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Compteurs par statut pour les tuiles de l'écran.
     *
     * Un seul `GROUP BY` plutôt que cinq `count()` : l'écran admin a déjà
     * une requête de liste, cinq requêtes de plus sur une table qui grossit
     * chaque mois se remarquent.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function compteursStatut(?int $enseignantId = null): array
    {
        return RapportMensuelEnseignant::query()
            ->when($enseignantId, fn ($query) => $query->where('enseignant_id', $enseignantId))
            ->when(
                ($p = request()->integer('periode_id')) > 0,
                fn ($query) => $query->where('periode_id', $p)
            )
            ->reorder()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();
    }
}
