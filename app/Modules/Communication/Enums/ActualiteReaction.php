<?php

namespace App\Modules\Communication\Enums;

class ActualiteReaction
{
    public const LIKE = 'like';

    public const DISLIKE = 'dislike';

    public const LOVE = 'love';

    public const BROKEN_HEART = 'broken_heart';

    public const APPLAUSE = 'applause';

    public const CONGRATS = 'congrats';

    public const SURPRISE = 'surprise';

    public const THANKS = 'thanks';

    public const VALIDES = [
        self::LIKE,
        self::DISLIKE,
        self::LOVE,
        self::BROKEN_HEART,
        self::APPLAUSE,
        self::CONGRATS,
        self::SURPRISE,
        self::THANKS,
    ];

    public const EMOJIS = [
        self::LIKE => '👍',
        self::DISLIKE => '👎',
        self::LOVE => '❤️',
        self::BROKEN_HEART => '💔',
        self::APPLAUSE => '👏',
        self::CONGRATS => '🎉',
        self::SURPRISE => '😮',
        self::THANKS => '🙏',
    ];

    public const LABELS = [
        self::LIKE => 'J\'aime',
        self::DISLIKE => 'Je n\'aime pas',
        self::LOVE => 'J\'adore',
        self::BROKEN_HEART => 'Déçu',
        self::APPLAUSE => 'Bravo',
        self::CONGRATS => 'Félicitations',
        self::SURPRISE => 'Surpris',
        self::THANKS => 'Merci',
    ];
}
