<?php

namespace App\Modules\Temoignages\Enums;

class TemoignageReactionType
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

    public const WEIGHTS = [
        self::LOVE => 3,
        self::APPLAUSE => 2,
        self::LIKE => 1,
        self::CONGRATS => 1,
        self::SURPRISE => 1,
        self::THANKS => 1,
        self::DISLIKE => 0,
        self::BROKEN_HEART => 0,
    ];

    public static function poids(string $reaction): int
    {
        return self::WEIGHTS[$reaction] ?? 0;
    }

    public static function scoreSql(): string
    {
        $cases = '';

        foreach (self::WEIGHTS as $reaction => $poids) {
            $cases .= "when reaction = '{$reaction}' then {$poids} ";
        }

        return 'sum(case '.$cases.'else 0 end)';
    }
}
