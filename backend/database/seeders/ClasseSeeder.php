<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classe;

class ClasseSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            ['nom' => 'CP1', 'sigle' => 'CP1'],
            ['nom' => 'CP2', 'sigle' => 'CP2'],
            ['nom' => 'CE1', 'sigle' => 'CE1'],
            ['nom' => 'CE2', 'sigle' => 'CE2'],
            ['nom' => 'CM1', 'sigle' => 'CM1'],
            ['nom' => 'CM2', 'sigle' => 'CM2'],
            ['nom' => '6ème', 'sigle' => '6e'],
            ['nom' => '5ème', 'sigle' => '5e'],
            ['nom' => '4ème', 'sigle' => '4e'],
            ['nom' => '3ème', 'sigle' => '3e'],
        ];

        foreach ($classes as $classe) {
            Classe::firstOrCreate($classe);
        }
    }
}