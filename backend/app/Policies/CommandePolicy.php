<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Commande;

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
        return $user->hasRole(['admin', 'gestionnaire']);
    }

    public function view(User $user, Commande $commande): bool
    {
        if ($user->hasRole(['admin', 'gestionnaire'])) {
            return true;
        }

        return $commande->user_id === $user->id;
    }

    public function update(User $user): bool
    {
        return $user->hasRole(['admin', 'gestionnaire']) || $user->hasPermissionTo('commande.update');
    }

    public function cancel(User $user): bool
    {
        return $user->hasRole(['admin', 'gestionnaire']) || $user->hasPermissionTo('commande.cancel');
    }

    public function manage(User $user): bool
    {
        return $user->hasRole(['admin', 'gestionnaire']);
    }
}
