<?php

namespace App\Modules\Communication\Enums;

class ActualiteStatut
{
    public const BROUILLON = 'brouillon';

    public const PUBLIE = 'publie';

    public const VALIDES = [
        self::BROUILLON,
        self::PUBLIE,
    ];
}
