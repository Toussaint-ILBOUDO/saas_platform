<?php

namespace App\Policies;

use App\Models\ContratCours;
use App\Models\User;
use App\Support\Roles;

class ContratCoursPolicy
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
        return $user->can('contrat.view');
    }

    public function view(User $user, ContratCours $contrat): bool
    {
        // Admin : uniquement basé sur la permission (pas de double
        // condition hasRole + can qui peut se contredire)
        if ($user->can('contrat.view')) {
            return true;
        }

        // Parent du contrat. `eleves.parent_id` référence `users.id` et non
        // `parent_profils.id` (séquence indépendante) : utiliser le profil
        // rattacherait ce parent aux contrats d'une autre famille.
        if (
            $user->parentProfil
            && $contrat->eleve
            && $contrat->eleve->parent_id === $user->id
        ) {
            return true;
        }

        // Enseignant affecté à ce contrat
        if (
            $user->enseignantProfil
            && $contrat->affectations()
                ->where('enseignant_id', $user->enseignantProfil->id)
                ->exists()
        ) {
            return true;
        }

        // Élève concerné par ce contrat (données propres)
        if (
            $user->eleve
            && $contrat->eleve_id === $user->eleve->id
        ) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('contrat.create');
    }

    public function update(User $user, ContratCours $contrat): bool
    {
        return $user->can('contrat.update');
    }

    public function delete(User $user, ContratCours $contrat): bool
    {
        return $user->can('contrat.delete');
    }
}