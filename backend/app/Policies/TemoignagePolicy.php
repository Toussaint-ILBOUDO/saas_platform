<?php

namespace App\Policies;

use App\Models\Temoignage;
use App\Models\User;

class TemoignagePolicy
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
        return $user->can('temoignage.view');
    }

    public function view(User $user, Temoignage $temoignage): bool
    {
        return $user->can('temoignage.view');
    }

    public function create(User $user): bool
    {
        return $user->can('temoignage.create');
    }

    public function update(User $user, Temoignage $temoignage): bool
    {
        return $user->can('temoignage.create') && $user->id === $temoignage->user_id;
    }

    public function delete(User $user, Temoignage $temoignage): bool
    {
        return $user->can('temoignage.moderate')
            || ($user->can('temoignage.create') && $user->id === $temoignage->user_id);
    }

    public function moderate(User $user): bool
    {
        return $user->can('temoignage.moderate');
    }
}
