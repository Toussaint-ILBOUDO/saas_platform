<?php

namespace App\Policies;

use App\Models\AffectationEnseignant;
use App\Models\CahierTexte;
use App\Models\Eleve;
use App\Models\User;

class CahierTextePolicy
{
    /**
     * Court-circuite tout : super-admin (et admin si vous voulez
     * qu'il ait un accès total sans dépendre des permissions).
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }

        return null; // laisse les autres méthodes décider
    }

    public function view(User $user, CahierTexte $cahier): bool
    {
        if ($user->enseignantProfil) {
            return $cahier->affectation
                && $cahier->affectation->enseignant_id === $user->enseignantProfil->id;
        }

        if ($user->parentProfil) {
            return $cahier->affectation
                && $cahier->affectation->contrat
                && $cahier->affectation->contrat->eleve
                && $cahier->affectation->contrat->eleve->parent_id === $user->parentProfil->id;
        }

        if ($user->eleve) {
            return $cahier->affectation
                && $cahier->affectation->contrat
                && $cahier->affectation->contrat->eleve_id === $user->eleve->id;
        }

        return false;
    }

    public function viewHistory(User $user, int $eleveId): bool
    {
        if ($user->enseignantProfil) {
            return AffectationEnseignant::query()
                ->where('enseignant_id', $user->enseignantProfil->id)
                ->whereHas('contrat', fn ($q) => $q->where('eleve_id', $eleveId))
                ->exists();
        }

        if ($user->parentProfil) {
            return Eleve::query()
                ->where('id', $eleveId)
                ->where('parent_id', $user->parentProfil->id)
                ->exists();
        }

        if ($user->eleve) {
            return $user->eleve->id === $eleveId;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('enseignant') && $user->enseignantProfil !== null;
    }

    public function update(User $user, CahierTexte $cahier): bool
    {
        if ($user->enseignantProfil) {
            return $cahier->affectation
                && $cahier->affectation->enseignant_id === $user->enseignantProfil->id;
        }

        return false;
    }
}