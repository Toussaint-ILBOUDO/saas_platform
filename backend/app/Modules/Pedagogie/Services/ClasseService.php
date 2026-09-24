<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\Classe;
use App\Models\Eleve;

class ClasseService
{
    public function paginate(int $perPage = 15)
    {
        return Classe::query()
            ->withCount('eleves')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Classe
    {
        return Classe::create($data);
    }

    public function update(
        Classe $classe,
        array $data
    ): Classe {
        $classe->update($data);

        return $classe;
    }

    public function delete(
        Classe $classe
    ): bool {
        return $classe->delete();
    }

    public function getStats(): array
    {
        $totalClasses = Classe::count();

        $totalEleves = Eleve::count();

        $classePlusRemplie = Classe::withCount('eleves')
            ->orderByDesc('eleves_count')
            ->first();

        $moyenneEleves = $totalClasses > 0
            ? round($totalEleves / $totalClasses)
            : 0;

        return [
            'total_classes' => $totalClasses,
            'total_eleves' => $totalEleves,
            'moyenne_eleves' => $moyenneEleves,
            'classe_plus_remplie' => $classePlusRemplie,
        ];
    }
}