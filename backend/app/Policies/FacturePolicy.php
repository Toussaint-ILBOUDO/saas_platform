<?php

namespace App\Policies;

use App\Models\Facture;
use App\Models\User;

class FacturePolicy
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
        return $user->can('facture.view');
    }

    public function view(User $user, Facture $facture): bool
    {
        if (
            $user->hasRole('admin')
            && $user->can('facture.view')
        ) {
            return true;
        }

        // parent concerné
        if (
            $user->hasRole('parent')
            && $facture->parent_id == $user->id
        ) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('facture.create');
    }

    public function update(User $user, Facture $facture): bool
    {
        return $user->can('facture.update');
    }

    public function delete(User $user, Facture $facture): bool
    {
        return false;
    }
}