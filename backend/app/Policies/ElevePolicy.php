<?php

namespace App\Policies;

use App\Models\Eleve;
use App\Models\User;

class ElevePolicy
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
        return $user->can('eleve.view');
    }

    public function view(User $user, Eleve $eleve): bool
    {
        if ($user->hasRole('admin') && $user->can('eleve.view')) {
            return true;
        }

        if (
            $user->hasRole('parent') &&
            $eleve->parent_id === $user->id
        ) {
            return true;
        }

        if (
            $user->hasRole('eleve') &&
            $eleve->user_id === $user->id
        ) {
            return true;
        }

        if ($user->hasRole('enseignant') && $user->enseignantProfil) {
            return \App\Models\AffectationEnseignant::query()
                ->where('enseignant_id', $user->enseignantProfil->id)
                ->whereHas('contrat', fn ($q) =>
                    $q->where('eleve_id', $eleve->id)
                )
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('eleve.create');
    }

    public function update(User $user, Eleve $eleve): bool
    {
        if ($user->hasRole('admin') && $user->can('eleve.update')) {
            return true;
        }

        if (
            $user->hasRole('parent') &&
            $eleve->parent_id === $user->id
        ) {
            return true;
        }

        if ($user->hasRole('enseignant') && $user->enseignantProfil) {
            return \App\Models\AffectationEnseignant::query()
                ->where('enseignant_id', $user->enseignantProfil->id)
                ->whereHas('contrat', fn ($q) =>
                    $q->where('eleve_id', $eleve->id)
                )
                ->exists();
        }

        return false;
    }

    public function delete(User $user, Eleve $eleve): bool
    {
        return $user->can('eleve.delete');
    }
}
