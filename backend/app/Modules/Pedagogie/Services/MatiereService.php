<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\Matiere;
use App\Support\Recherche;

class MatiereService
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        return Matiere::query()
            ->withCount([
                'enseignants',
                'affectations',
            ])
            // Comme pour les classes : recherche et filtre en base, jamais sur
            // la page déjà paginée.
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => Recherche::likeInsensible($query, ['nom', 'sigle'], $search)
            )
            ->when(
                isset($filters['actif']) && $filters['actif'] !== '',
                fn ($query) => $query->where('actif', $filters['actif'])
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Matiere
    {
        Matiere::create($data);

        // `actif` n'est pas une donnée de saisie : il vient du défaut de la
        // colonne. Sans relecture, le modèle retourné porterait un attribut
        // absent → `null`, et l'API renverrait `actif: false` à tort.
        return Matiere::query()->latest('id')->firstOrFail();
    }

    public function update(
        Matiere $matiere,
        array $data
    ): Matiere {
        $matiere->update($data);

        return $matiere->refresh();
    }

    /**
     * Désactivation (D-053) : retire la matière des listes de saisie sans
     * toucher à l'historique — affectations, objectifs et rapports la
     * référencent. C'est le seul moyen de « retire » une matière utilisée.
     */
    public function activate(Matiere $matiere): void
    {
        $matiere->update([
            'actif' => true,
        ]);
    }

    public function deactivate(Matiere $matiere): void
    {
        $matiere->update([
            'actif' => false,
        ]);
    }

    public function delete(
        Matiere $matiere
    ): bool {
        return $matiere->delete();
    }
}