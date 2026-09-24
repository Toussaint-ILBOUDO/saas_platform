<?php

namespace App\Policies;

use App\Models\Actualite;
use App\Models\User;

class ActualitePolicy
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
        return $user->can('actualite.view');
    }

    public function view(User $user, Actualite $actualite): bool
    {
        return $user->can('actualite.view');
    }

    public function create(User $user): bool
    {
        return $user->can('actualite.create');
    }

    public function update(User $user, Actualite $actualite): bool
    {
        return $user->can('actualite.update');
    }

    public function delete(User $user, Actualite $actualite): bool
    {
        return $user->can('actualite.delete');
    }
}
