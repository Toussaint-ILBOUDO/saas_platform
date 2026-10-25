<?php

namespace Database\Seeders;

use App\Models\TypeAjustement;
use Illuminate\Database\Seeder;

class TypeAjustementSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Bulletin de paie — majorations (crédit) et retenues (débit).
            ['libelle' => 'Prime',            'direction' => 'credit', 'is_active' => true],
            ['libelle' => 'Bonus',            'direction' => 'credit', 'is_active' => true],
            ['libelle' => 'Indemnite',        'direction' => 'credit', 'is_active' => true],
            ['libelle' => 'Retenue',          'direction' => 'debit',  'is_active' => true],
            ['libelle' => 'Penalite',         'direction' => 'debit',  'is_active' => true],
            // Facture parent — frais supplémentaires (crédit) et remises (débit).
            ['libelle' => 'Autres frais',     'direction' => 'credit', 'is_active' => true],
            ['libelle' => 'Remise',           'direction' => 'debit',  'is_active' => true],
            ['libelle' => 'Autre ajustement', 'direction' => 'credit', 'is_active' => true],
        ];

        foreach ($types as $type) {
            TypeAjustement::updateOrCreate(
                ['libelle' => $type['libelle']],
                $type
            );
        }
    }
}
