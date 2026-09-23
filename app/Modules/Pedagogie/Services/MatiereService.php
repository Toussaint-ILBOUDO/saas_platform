<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\Matiere;

class MatiereService
{
    public function paginate(int $perPage = 15)
    {
        return Matiere::query()
            ->withCount([
                'enseignants',
                'affectations',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Matiere
    {
        return Matiere::create($data);
    }

    public function update(
        Matiere $matiere,
        array $data
    ): Matiere {
        $matiere->update($data);

        return $matiere->refresh();
    }

    public function delete(
        Matiere $matiere
    ): bool {
        return $matiere->delete();
    }
}