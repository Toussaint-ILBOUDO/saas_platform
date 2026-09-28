<?php

namespace App\Console\Commands;

use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Réinitialise le mot de passe du compte admin_cabinet d'un tenant.
 * Le modèle User a le cast « password => hashed » : on sauvegarde le mot de
 * passe en clair, la colonne est hachée une seule fois automatiquement.
 * Usage :
 *   php artisan cabinet:reset-admin-password magis-plus-center
 *   php artisan cabinet:reset-admin-password magis-plus-center --password="TonMdp#2026"
 */
class ReinitialiserMotDePasseAdminCabinet extends Command
{
    protected $signature = 'cabinet:reset-admin-password
        {cabinet : id du tenant (ex. magis-plus-center)}
        {--password= : nouveau mot de passe (demandé en interactif si absent)}';

    protected $description = 'Réinitialise le mot de passe du compte admin_cabinet d\'un tenant';

    public function handle(): int
    {
        $cabinet = Cabinet::find($this->argument('cabinet'));
        if (! $cabinet) {
            $this->error("Tenant « {$this->argument('cabinet')} » introuvable.");

            return self::FAILURE;
        }

        $motDePasse = $this->option('password');
        if (! $motDePasse) {
            $motDePasse = $this->secret('Nouveau mot de passe admin (minimum 8 caractères) ?');
        }
        if (strlen((string) $motDePasse) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');

            return self::FAILURE;
        }

        tenancy()->initialize($cabinet);
        try {
            $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin_cabinet'))
                ->orderBy('id')
                ->first();
            if (! $admin) {
                $this->error("Aucun compte admin_cabinet dans le tenant « {$cabinet->id} ».");

                return self::FAILURE;
            }
            $admin->update(['password' => $motDePasse]);
            $this->info("Mot de passe réinitialisé pour {$admin->email} (rôle admin_cabinet).");
        } finally {
            tenancy()->end();
        }

        return self::SUCCESS;
    }
}