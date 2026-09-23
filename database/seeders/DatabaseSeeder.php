<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            ClasseSeeder::class,
            MatiereSeeder::class,
            TypeCoursSeeder::class,
            ProductionUserSeeder::class,
            TypeAjustementSeeder::class,
            TypeDocumentSeeder::class,
            TypeCommissionSeeder::class,
            PeriodeDocumentSeeder::class,
            LibrairieSeeder::class,
            FaqSeeder::class,
        ]);
    }
}