<?php

namespace App\Modules\Pedagogie\Enums;

/**
 * Jours de la semaine d'un créneau régulier de planning de cours.
 *
 * 1 = Lundi … 7 = Dimanche (mapping métier indépendant de Carbon).
 */
class PlanningJourSemaine
{
    public const LUNDI = 1;
    public const MARDI = 2;
    public const MERCREDI = 3;
    public const JEUDI = 4;
    public const VENDREDI = 5;
    public const SAMEDI = 6;
    public const DIMANCHE = 7;

    public const VALIDES = [
        self::LUNDI,
        self::MARDI,
        self::MERCREDI,
        self::JEUDI,
        self::VENDREDI,
        self::SAMEDI,
        self::DIMANCHE,
    ];

    public const LABELS = [
        self::LUNDI => 'Lundi',
        self::MARDI => 'Mardi',
        self::MERCREDI => 'Mercredi',
        self::JEUDI => 'Jeudi',
        self::VENDREDI => 'Vendredi',
        self::SAMEDI => 'Samedi',
        self::DIMANCHE => 'Dimanche',
    ];

    public static function label(int $jour): string
    {
        return self::LABELS[$jour] ?? '';
    }
}