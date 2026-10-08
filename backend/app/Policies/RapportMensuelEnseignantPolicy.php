<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;
use App\Models\RapportMensuelEnseignant;

class RapportMensuelEnseignantPolicy
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
        return Roles::estEnseignant($user)
            || $user->can('rapport.view');
    }

    public function view(
        User $user,
        RapportMensuelEnseignant $rapport
    ): bool {


        if (
            Roles::estAdmin($user)
            &&
            $user->can('rapport.view')
        ) {
            return true;
        }


        if (
            Roles::estEnseignant($user)
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
        return Roles::estEnseignant($user);
    }

    public function update(
        User $user,
        RapportMensuelEnseignant $rapport
    ): bool {

        if (
            Roles::estEnseignant($user)
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
            Roles::estEnseignant($user)
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

    /**
     * T7A.7 — Suppression côté API.
     *
     * La policy `delete` bloque déjà un rapport validé (403) ; c'est exact.
     * Mais pour l'API, l'état est une décision du service : la suppression
     * d'un rapport validé doit renvoyer une erreur 422 explicite (« déjà
     * facturé »), pas un 403 muet. Cette ability ne vérifie donc QUE la
     * propriété ; la machine à états reste dans `RapportMensuelService`.
     */
    public function supprimer(
        User $user,
        RapportMensuelEnseignant $rapport
    ): bool {
        return Roles::estEnseignant($user)
            && $rapport->enseignant
            && $rapport->enseignant?->user_id == $user->id;
    }

    /**
     * T7A.7 — Validation administrative (D-051).
     *
     * C'est l'action qui fige le rapport : ses heures deviennent la source de
     * la facture parent et du bulletin de paie. Réservée à l'administration,
     * et seulement sur un rapport soumis — le service re-vérifie ce statut
     * dans une transaction, la policy ne sert qu'à ouvrir/fermer la porte.
     */
    public function valider(
        User $user,
        RapportMensuelEnseignant $rapport
    ): bool {
        return Roles::estAdmin($user) && $rapport->estSoumis();
    }

    /**
     * T7A.7 — Rejet motivé par l'administration.
     */
    public function rejeter(
        User $user,
        RapportMensuelEnseignant $rapport
    ): bool {
        return Roles::estAdmin($user) && $rapport->estSoumis();
    }
}