<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;

/**
 * Premier super-admin de la plateforme (T2.1 / D-015).
 *
 * Exécuté par le propriétaire sur la base centrale (insertion en base) :
 *
 *   php artisan db:seed --class=SuperAdminSeeder
 *
 * Identifiants par défaut (à surcharger via les variables d'environnement) :
 *   LANDLORD_EMAIL    (défaut : admin@saascd.test)
 *   LANDLORD_PASSWORD (défaut : placé ci-dessous — changer après première connexion)
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        SuperAdmin::updateOrCreate(
            ['email' => env('LANDLORD_EMAIL', 'admin@saascd.test')],
            [
                'nom' => 'Super Admin',
                'password' => env('LANDLORD_PASSWORD', 'Ch@ngeMoi2026!'),
            ]
        );
    }
}