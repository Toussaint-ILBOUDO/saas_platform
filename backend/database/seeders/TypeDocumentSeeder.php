<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TypeDocument;

class TypeDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Cours', 'sigle' => 'COURS'],
            ['nom' => 'Exercice', 'sigle' => 'EXERC'],
            ['nom' => 'Évaluation', 'sigle' => 'EVAL'],
            ['nom' => 'Support de cours', 'sigle' => 'SUPC'],
            ['nom' => 'Méthodologie', 'sigle' => 'METHO'],
            ['nom' => 'Synthèse', 'sigle' => 'SYNTH'],
            ['nom' => 'Fiche de révision', 'sigle' => 'FICHE'],
            ['nom' => 'Travaux pratiques', 'sigle' => 'TP'],
            ['nom' => 'Autre', 'sigle' => 'AUTRE'],
        ];

        foreach ($types as $type) {
            TypeDocument::firstOrCreate(
                ['sigle' => $type['sigle']],
                ['nom' => $type['nom']]
            );
        }
    }
}
