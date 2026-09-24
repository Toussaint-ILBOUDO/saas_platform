<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\ContratCours;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContratCoursQueryService
{
    public function paginate(
        int $perPage = 20
    ): LengthAwarePaginator {
        return ContratCours::query()
            ->with([
                'eleve.user',
                'typeCours',
                'affectations.enseignant.user',
                'affectations.matiere',
            ])
            ->latest()
            ->paginate($perPage);
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

    public function show(
        ContratCours $contrat
    ): ContratCours {
        return $contrat->load([
            'eleve.user',
            'typeCours',
            'affectations.enseignant.user',
            'affectations.matiere',
        ]);
    }
}