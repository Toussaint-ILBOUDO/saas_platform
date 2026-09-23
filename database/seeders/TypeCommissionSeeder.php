<?php

namespace Database\Seeders;

use App\Models\TypeCommission;
use Illuminate\Database\Seeder;

class TypeCommissionSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Cours',
            'Inscription',
            'Vente',
            'Pénalité',
            'Remise',
        ];

        foreach ($types as $type) {
            TypeCommission::updateOrCreate(
                ['nom_du_type' => $type]
            );
        }
    }
}
