<?php

namespace App\Modules\Communication\Enums;

/**
 * Destinataires métier d'une actualité.
 *
 * Les valeurs exposées par l'interface (formulaire) sont des libellés métier
 * au pluriel (« parents », « eleves », « enseignants ») qui ne correspondent
 * PAS aux noms des rôles Spatie enregistrés (singulier : « parent », etc.).
 *
 * Ce fichier est la SOURCE UNIQUE de la correspondance valeur métier -> rôle
 * Spatie. Ne jamais dupliquer ces tableaux ailleurs.
 */
class ActualiteDestinataire
{
    public const PARENTS = 'parents';

    public const ELEVES = 'eleves';

    public const ENSEIGNANTS = 'enseignants';

    /**
     * Valeurs acceptées par le formulaire et le Form Request.
     */
    public const VALIDES = [
        self::PARENTS,
        self::ELEVES,
        self::ENSEIGNANTS,
    ];

    public const LABELS = [
        self::PARENTS => 'Parents',
        self::ELEVES => 'Élèves',
        self::ENSEIGNANTS => 'Enseignants',
    ];

    public const ICONS = [
        self::PARENTS => 'bi-people',
        self::ELEVES => 'bi-backpack',
        self::ENSEIGNANTS => 'bi-person-workspace',
    ];

    /**
     * Correspondance valeur métier -> rôle Spatie (guard web).
     */
    public const ROLE_MAP = [
        self::PARENTS => 'parent',
        self::ELEVES => 'eleve',
        self::ENSEIGNANTS => 'enseignant',
    ];

    /**
     * Traduit une valeur métier vers le nom du rôle Spatie correspondant.
     * Retourne null si la valeur est inconnue (aucune exception).
     */
    public static function toSpatieRole(string $value): ?string
    {
        return self::ROLE_MAP[$value] ?? null;
    }

    /**
     * Traduit le nom d'un rôle Spatie vers la valeur métier correspondante.
     * Retourne null si le rôle ne correspond à aucun destinataire connu.
     */
    public static function fromSpatieRole(string $role): ?string
    {
        $value = array_search($role, self::ROLE_MAP, true);

        return $value === false ? null : $value;
    }

    /**
     * Vérifie qu'une valeur métier est acceptée.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::VALIDES, true);
    }
}
