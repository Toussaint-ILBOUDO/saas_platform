<?php

namespace App\Modules\Finance\Services;

use App\Models\BulletinPaie;
use App\Support\Recherche;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * T7A.9 — Lecture des bulletins de paie.
 *
 * Deux index qui servent deux écrans : l'enseignant ne voit que SES bulletins
 * (scopés à `enseignant_id`, et le détail d'un autre bulletin est un 404,
 * jamais un 403) et l'administration voit tout le cabinet avec la recherche
 * par enseignant.
 */
class BulletinPaieQueryService
{
    /**
     * Index enseignant : uniquement les bulletins du profil connecté.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginerPourEnseignant(
        int $enseignantProfilId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->appliquerFiltres(
            $this->base()->where('enseignant_id', $enseignantProfilId),
            $filters
        )
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Index administration : tous les bulletins du cabinet.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginerPourAdmin(
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->appliquerFiltres($this->base(), $filters)
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Bulletin unique résolu dans le périmètre de l'enseignant : 404 (et non
     * 403) si le bulletin n'est pas le sien.
     */
    public function trouverPourEnseignant(int $enseignantProfilId, int $bulletinId): BulletinPaie
    {
        return BulletinPaie::query()
            ->where('enseignant_id', $enseignantProfilId)
            ->where('id', $bulletinId)
            ->firstOrFail();
    }

    /**
     * Loads default pour la liste.
     */
    private function base()
    {
        return BulletinPaie::query()
            ->with([
                'periode',
                'enseignant.user',
            ]);
    }

    /**
     * Applique les filtres communs sur le query builder fourni.
     *
     * @param  array<string, mixed>  $filters
     */
    private function appliquerFiltres($query, array $filters)
    {
        return $query
            ->when(
                $filters['periode_id'] ?? null,
                fn ($q, $id) => $q->where('periode_id', $id)
            )
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($q) => $q->where('statut', $filters['statut'])
            )
            ->when(
                $filters['search'] ?? null,
                // La recherche porte sur l'enseignant (nom / prénom).
                fn ($q, $search) => $q->whereHas(
                    'enseignant.user',
                    fn ($u) => Recherche::likeInsensible($u, ['nom', 'prenom'], $search)
                )
            );
    }
}