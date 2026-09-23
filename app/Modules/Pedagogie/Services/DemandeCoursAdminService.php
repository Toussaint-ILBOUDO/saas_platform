<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\DemandeCours;

class DemandeCoursAdminService
{
    public function paginate(int $perPage = 15)
    {
        return DemandeCours::query()
            ->with([
                'classe',
                'typeCours',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function getStats(): array
    {
        return [
            'total' => DemandeCours::count(),

            'en_attente' => DemandeCours::where(
                'statut',
                'en_attente'
            )->count(),

            'traitees' => DemandeCours::where(
                'statut',
                'traitee'
            )->count(),
        ];
    }

    public function valider(
        DemandeCours $demandeCours
    ): void {
        $demandeCours->update([
            'statut' => 'traitee',
        ]);
    }
}