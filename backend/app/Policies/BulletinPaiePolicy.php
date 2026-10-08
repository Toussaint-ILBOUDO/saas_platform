<?php

namespace App\Policies;

use App\Models\BulletinPaie;
use App\Models\User;
use App\Support\Roles;

class BulletinPaiePolicy
{
    /**
     * Admin ou super-admin peut tout faire.
     */
    public function before(
        User $user,
        string $ability
    ): ?bool {
        if (Roles::estAdmin($user)) {
            return true;
        }

        return null;
    }

    /**
     * Un enseignant ne peut voir que ses propres bulletins.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BulletinPaie $bulletin): bool
    {
        if (Roles::estAdmin($user)) {
            return true;
        }

        return $user->enseignantProfil
            && $bulletin->enseignant_id === $user->enseignantProfil->id;
    }

    /**
     * Seul l'admin peut générer.
     */
    public function create(User $user): bool
    {
        return Roles::estAdmin($user);
    }

    /**
     * L'enseignant ne peut que consulter, valider ou contester.
     */
    public function update(User $user, BulletinPaie $bulletin): bool
    {
        if (Roles::estAdmin($user)) {
            return true;
        }

        return $user->enseignantProfil
            && $bulletin->enseignant_id === $user->enseignantProfil->id;
    }

    public function delete(User $user): bool
    {
        return Roles::estAdmin($user);
    }

    public function payer(User $user): bool
    {
        return Roles::estAdmin($user);
    }

    public function contester(User $user, BulletinPaie $bulletin): bool
    {
        return $user->enseignantProfil
            && $bulletin->enseignant_id === $user->enseignantProfil->id;
    }

    public function valider(User $user, BulletinPaie $bulletin): bool
    {
        return $user->enseignantProfil
            && $bulletin->enseignant_id === $user->enseignantProfil->id;
    }
}
