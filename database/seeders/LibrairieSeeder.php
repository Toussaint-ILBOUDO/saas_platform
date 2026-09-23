<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategorieProduit;

class LibrairieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Manuels scolaires', 'ordre' => 1],
            ['nom' => 'Cahiers et fournitures', 'ordre' => 2],
            ['nom' => 'Fournitures de bureau', 'ordre' => 3],
            ['nom' => 'Matériel informatique', 'ordre' => 4],
            ['nom' => 'Articles sportifs', 'ordre' => 5],
            ['nom' => 'Autres', 'ordre' => 6],
        ];

        foreach ($categories as $categorie) {
            CategorieProduit::firstOrCreate(
                ['nom' => $categorie['nom']],
                [
                    'ordre' => $categorie['ordre'],
                    'is_active' => true,
                ]
            );
        }
    }
}
