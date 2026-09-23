<?php

namespace App\Modules\Communication\Enums;

class ActualiteCanal
{
    public const INTERNE = 'interne';

    public const EMAIL = 'email';

    public const INTERNE_EMAIL = 'interne_email';

    public const VALIDES = [
        self::INTERNE,
        self::EMAIL,
        self::INTERNE_EMAIL,
    ];

    public const LABELS = [
        self::INTERNE => 'Notification interne',
        self::EMAIL => 'Email',
        self::INTERNE_EMAIL => 'Notification interne + Email',
    ];
}
