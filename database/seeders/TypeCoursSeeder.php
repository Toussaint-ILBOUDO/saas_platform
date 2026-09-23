<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TypeCours;

class TypeCoursSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'A domicile',
            'Renforcement',
            'En ligne',
            'Cours de groupe',
        ];

        foreach ($types as $type) {
            TypeCours::firstOrCreate([
                'libelle' => $type,
            ]);
        }
    }
}