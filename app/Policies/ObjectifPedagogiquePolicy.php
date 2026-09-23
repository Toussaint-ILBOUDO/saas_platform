<?php

namespace App\Policies;

use App\Models\ObjectifPedagogique;
use App\Models\User;

class ObjectifPedagogiquePolicy
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
        return true;
    }

    public function view(User $user, ObjectifPedagogique $objectif): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('enseignant') && $user->enseignantProfil) {
            return \App\Models\AffectationEnseignant::query()
                ->where('enseignant_id', $user->enseignantProfil->id)
                ->whereHas('contrat.eleve', fn ($q) =>
                    $q->where('id', $objectif->eleve_id)
                )
                ->exists();
        }

        if ($user->hasRole('parent')) {
            return \App\Models\Eleve::query()
                ->where('id', $objectif->eleve_id)
                ->where('parent_id', $user->id)
                ->exists();
        }

        if ($user->hasRole('eleve')) {
            return $user->eleve && $user->eleve->id === $objectif->eleve_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('enseignant') && $user->enseignantProfil !== null;
    }

    public function update(User $user, ObjectifPedagogique $objectif): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('enseignant') && $user->enseignantProfil) {
            return \App\Models\AffectationEnseignant::query()
                ->where('enseignant_id', $user->enseignantProfil->id)
                ->whereHas('contrat.eleve', fn ($q) =>
                    $q->where('id', $objectif->eleve_id)
                )
                ->exists();
        }

        return false;
    }

    public function delete(User $user, ObjectifPedagogique $objectif): bool
    {
        return $user->hasRole('admin');
    }
}
