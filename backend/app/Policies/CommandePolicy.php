<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Commande;
use App\Support\Roles;

/**
 * Commandes de la librairie.
 *
 * Les rôles « admin » et « gestionnaire » du code KEduc n'existent pas dans
 * une base cabinet (D-007) : les rôles réels sont `admin_cabinet` et
 * `gestionnaire_librairie` (voir `App\Support\Roles`).
 */
class CommandePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return Roles::estAdmin($user) || $user->hasRole('gestionnaire_librairie');
    }

    public function view(User $user, Commande $commande): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return $commande->user_id !== null && $commande->user_id === $user->id;
    }

    public function update(User $user): bool
    {
        return Roles::estAdmin($user) || $user->hasRole('gestionnaire_librairie');
    }

    public function cancel(User $user): bool
    {
        return Roles::estAdmin($user) || $user->hasRole('gestionnaire_librairie');
    }

    public function manage(User $user): bool
    {
        return Roles::estAdmin($user) || $user->hasRole('gestionnaire_librairie');
    }
}
