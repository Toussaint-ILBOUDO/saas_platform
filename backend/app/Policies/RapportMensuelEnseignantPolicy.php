<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RapportMensuelEnseignant;

class RapportMensuelEnseignantPolicy
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
        return $user->hasRole('enseignant')
            || $user->can('rapport.view');
    }

    public function view(
        User $user,
        RapportMensuelEnseignant $rapport
    ): bool {


        if (
            $user->hasRole('admin')
            &&
            $user->can('rapport.view')
        ) {
            return true;
        }


        if (
            $user->hasRole('enseignant')
            &&
            $rapport->enseignant
            &&
            $rapport->enseignant->user_id == $user->id
        ) {
            return true;
        }


        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('enseignant');
    }

    public function update(
        User $user,
        RapportMensuelEnseignant $rapport
    ): bool {

        if (
            $user->hasRole('enseignant')
            &&
            $rapport->enseignant
            &&
            $rapport->enseignant?->user_id == $user->id
            &&
            $rapport->statut !== 'valide'
        ) {
            return true;
        }


        return false;
    }

    public function delete(
        User $user,
        RapportMensuelEnseignant $rapport
    ): bool {

        if (
            $user->hasRole('enseignant')
            &&
            $rapport->enseignant
            &&
            $rapport->enseignant?->user_id == $user->id
            &&
            $rapport->statut !== 'valide'
        ) {
            return true;
        }


        return false;
    }
}