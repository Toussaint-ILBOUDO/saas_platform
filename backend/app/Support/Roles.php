<?php

namespace App\Support;

use App\Models\User;

/**
 * Rôles du cabinet (D-007 / D-051).
 *
 * Les bases tenant ne contiennent QUE ces 5 rôles (voir `TenantDatabaseSeeder`) :
 * `admin_cabinet`, `enseignant`, `parent`, `eleve`, `gestionnaire_librairie`.
 *
 * Le code hérité de KEduc testait `admin` et `super-admin` : dans une base cabinet
 * réelle ces deux rôles n'existent jamais, donc toutes les branches « admin » des
 * policies tombaient à `false` et les routes `role:admin` répondaient 403.
 * Ce point centralise la définition du « staff cabinet » — toute nouvelle policy
 * ou tout nouveau contrôleur doit passer par ici.
 */
final class Roles
{
    public const ADMIN = 'admin_cabinet';

    public const ROLES_TENANT = [
        self::ADMIN,
        'enseignant',
        'parent',
        'eleve',
        'gestionnaire_librairie',
    ];

    /**
     * Staff d'administration du cabinet (équivalent KEduc « admin »).
     * `admin` et `super-admin` sont acceptés par tolérance : ils n'existent pas
     * dans une base tenant mais peuvent subsister dans d'anciennes données ou
     * dans le Landlord.
     */
    public static function estAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole([self::ADMIN, 'admin', 'super-admin']);
    }

    public static function estEnseignant(?User $user): bool
    {
        return $user?->hasRole('enseignant') === true;
    }

    public static function estParent(?User $user): bool
    {
        return $user?->hasRole('parent') === true;
    }

    public static function estEleve(?User $user): bool
    {
        return $user?->hasRole('eleve') === true;
    }

    /**
     * Staff : admin cabinet ou gestionnaire de librairie (cible des
     * notifications opérationnelles de `NotificationDispatcher`).
     */
    public static function estStaff(?User $user): bool
    {
        return $user?->hasAnyRole(self::ROLES_STAFF) === true;
    }

    /**
     * Noms des rôles « staff » du cabinet, à passer à `User::role([...])`.
     *
     * Centralisé ici pour que la résolution des destinataires des
     * notifications soit identique partout : interroger `['admin',
     * 'super-admin']` renvoie zéro résultat dans une base tenant (D-007).
     */
    public const ROLES_STAFF = [
        self::ADMIN,
        'gestionnaire_librairie',
    ];
}
