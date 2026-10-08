<?php

namespace App\Policies;

use App\Models\Facture;
use App\Models\User;
use App\Support\Roles;

class FacturePolicy
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
        return $user->can('facture.view');
    }

    public function view(User $user, Facture $facture): bool
    {
        if (
            Roles::estAdmin($user)
            && $user->can('facture.view')
        ) {
            return true;
        }

        // parent concerné
        if (
            Roles::estParent($user)
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

    /**
     * Enregistrement d'un règlement : un acte administratif. Le parent ne
     * règle pas « lui-même » une facture dans l'API — l'écran parent est en
     * lecture seule. L'état réel (facture déjà payée) reste tranché par le
     * service `FacturationService::marquerPaye()`.
     */
    public function payer(User $user, Facture $facture): bool
    {
        return $user->can('facture.update');
    }

    public function delete(User $user, Facture $facture): bool
    {
        return false;
    }
}