<?php

namespace App\Policies;

use App\Models\ObjectifPedagogique;
use App\Models\User;
use App\Support\Roles;

class ObjectifPedagogiquePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (Roles::estAdmin($user)) {
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
        if (Roles::estAdmin($user)) {
            return true;
        }

        if (Roles::estEnseignant($user) && $user->enseignantProfil) {
            return \App\Models\AffectationEnseignant::query()
                ->where('enseignant_id', $user->enseignantProfil->id)
                ->whereHas('contrat.eleve', fn ($q) =>
                    $q->where('id', $objectif->eleve_id)
                )
                ->exists();
        }

        if (Roles::estParent($user)) {
            return \App\Models\Eleve::query()
                ->where('id', $objectif->eleve_id)
                ->where('parent_id', $user->id)
                ->exists();
        }

        if (Roles::estEleve($user)) {
            return $user->eleve && $user->eleve->id === $objectif->eleve_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return Roles::estEnseignant($user) && $user->enseignantProfil !== null;
    }

    public function update(User $user, ObjectifPedagogique $objectif): bool
    {
        if (Roles::estAdmin($user)) {
            return true;
        }

        if (Roles::estEnseignant($user) && $user->enseignantProfil) {
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
        return Roles::estAdmin($user);
    }
}
