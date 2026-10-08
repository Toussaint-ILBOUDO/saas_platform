<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\TypeCours;
use App\Support\Recherche;

class TypeCoursService
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        return TypeCours::query()
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => Recherche::likeInsensible($query, ['libelle', 'code'], $search)
            )
            ->when(
                isset($filters['actif']) && $filters['actif'] !== '',
                fn ($query) => $query->where('actif', $filters['actif'])
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): TypeCours
    {
        $data['actif'] = $data['actif'] ?? true;

        return TypeCours::create($data);
    }

    public function update(TypeCours $typeCours, array $data): TypeCours
    {
        $typeCours->update($data);

        return $typeCours->fresh();
    }

    public function activate(TypeCours $typeCours): void
    {
        $typeCours->update([
            'actif' => true,
        ]);
    }

    public function deactivate(TypeCours $typeCours): void
    {
        $typeCours->update([
            'actif' => false,
        ]);
    }
}