<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Matiere;

class MatiereSeeder extends Seeder
{
    public function run(): void
    {
        $matieres = [
            ['nom' => 'Mathématiques', 'sigle' => 'MATHS'],
            ['nom' => 'Français', 'sigle' => 'FR'],
            ['nom' => 'Physique-Chimie', 'sigle' => 'PC'],
            ['nom' => 'Sciences de la Vie et de la Terre', 'sigle' => 'SVT'],
            ['nom' => 'Histoire-Géographie', 'sigle' => 'HG'],
            ['nom' => 'Anglais', 'sigle' => 'ANG'],
            ['nom' => 'Informatique', 'sigle' => 'INFO'],
        ];

        foreach ($matieres as $matiere) {
            Matiere::firstOrCreate($matiere);
        }
    }
}