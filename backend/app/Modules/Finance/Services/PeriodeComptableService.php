<?php

namespace App\Modules\Finance\Services;

use App\Models\PeriodeComptable;
use Illuminate\Support\Facades\DB;

class PeriodeComptableService
{
    public function create(array $data): PeriodeComptable
    {
        return DB::transaction(function () use ($data) {
            return PeriodeComptable::create([
                'label' => $data['label'],
                'date_debut' => $data['date_debut'],
                'date_fin' => $data['date_fin'],
                'type' => $data['type'] ?? 'mensuel',
                'statut' => $data['statut'] ?? 'ouverte',
            ]);
        });
    }

    public function update(PeriodeComptable $periode, array $data): PeriodeComptable
    {
        return DB::transaction(function () use ($periode, $data) {
            $periode->update($data);
            return $periode->fresh();
        });
    }

    public function close(PeriodeComptable $periode): PeriodeComptable
    {
        return DB::transaction(function () use ($periode) {
            $periode->update([
                'statut' => 'cloturee'
            ]);

            return $periode->fresh();
        });
    }

    public function delete(PeriodeComptable $periode): bool
    {
        return $periode->delete();
    }

    public function list(int $perPage = 15)
    {
        return PeriodeComptable::orderBy('date_debut', 'desc')->paginate($perPage);;
    }
}