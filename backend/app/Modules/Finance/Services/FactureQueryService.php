<?php

namespace App\Modules\Finance\Services;

use App\Models\Facture;
use App\Support\Recherche;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * T7A.8 — Lecture des factures parent.
 *
 * Deux index qui servent deux écrans : le parent ne voit que SES factures
 * (scopées à `parent_id`, jamais modifiables) et l'administration voit tout
 * le cabinet. Les deux partagent les mêmes filtres ; seul le périmètre
 * change — et il n'est jamais déduit d'un identifiant devinable par le
 * client.
 */
class FactureQueryService
{
    /**
     * Index parent : uniquement les factures dont la facture est adressée au
     * parent connecté.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginerPourParent(
        int $parentId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->appliquerFiltres(
            $this->base()->where('parent_id', $parentId),
            $filters
        )
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Index administration : toutes les factures du cabinet.
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
     * Facture unique résolue dans le périmètre du parent : 404 (et non 403)
     * si la facture n'est pas la sienne.
     */
    public function trouverPourParent(int $parentId, int $factureId): Facture
    {
        return Facture::query()
            ->where('parent_id', $parentId)
            ->where('id', $factureId)
            ->firstOrFail();
    }

    private function base()
    {
        return Facture::query()
            ->with([
                'periode',
                'parent',
                'contrat.eleve.user',
                'contrat.typeCours',
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
                fn ($q) => $q->where('statut_paiement', $filters['statut'])
            )
            ->when(
                $filters['search'] ?? null,
                // La recherche porte sur l'élève (le web cherchait « nom /
                // prénom de l'élève ») : le parent ne cherche que ses enfants,
                // l'administratif cherche dans tout le cabinet.
                fn ($q, $search) => $q->where(function ($sous) use ($search) {
                    $sous->whereHas(
                        'eleve.user',
                        fn ($u) => Recherche::likeInsensible($u, ['nom', 'prenom'], $search)
                    );
                })
            );
    }
}