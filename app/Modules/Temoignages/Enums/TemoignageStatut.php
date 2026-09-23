<?php

namespace App\Modules\Temoignages\Enums;

class TemoignageStatut
{
    public const PUBLIE = 'publie';

    public const MASQUE = 'masque';

    public const VALIDES = [
        self::PUBLIE,
        self::MASQUE,
    ];

    public const LABELS = [
        self::PUBLIE => 'Publié',
        self::MASQUE => 'Masqué',
    ];

    public static function label(string $statut): string
    {
        return self::LABELS[$statut] ?? $statut;
    }
}
