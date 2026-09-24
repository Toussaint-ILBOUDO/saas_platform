<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PeriodeDocument;

class PeriodeDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $periodes = [
            ['nom' => 'Premier trimestre', 'sigle' => 'T1'],
            ['nom' => 'Deuxième trimestre', 'sigle' => 'T2'],
            ['nom' => 'Troisième trimestre', 'sigle' => 'T3'],
            ['nom' => 'Semestre 1', 'sigle' => 'S1'],
            ['nom' => 'Semestre 2', 'sigle' => 'S2'],
            ['nom' => 'Annuel', 'sigle' => 'ANN'],
            ['nom' => 'Autre', 'sigle' => 'AUTRE'],
        ];

        foreach ($periodes as $periode) {
            PeriodeDocument::firstOrCreate(
                ['sigle' => $periode['sigle']],
                ['nom' => $periode['nom']]
            );
        }
    }
}
