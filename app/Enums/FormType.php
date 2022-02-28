<?php

declare(strict_types=1);

namespace App\Enums;

use MyCLabs\Enum\Enum;

class FormType extends Enum
{
    public const FORM_TYPE_IN_PERSON = 0;
    public const FORM_TYPE_HIGH_FLEX = 1;
    public const FORM_TYPE_ON_DEMAND = 2;
    public const FORM_TYPE_OTHER = 3;

    private static array $labels = [
        self::FORM_TYPE_IN_PERSON => '対面',
        self::FORM_TYPE_HIGH_FLEX => 'ハイフレックス',
        self::FORM_TYPE_ON_DEMAND => '録画視聴',
        self::FORM_TYPE_OTHER => 'その他',
    ];

    public static function label(self $course): string
    {
        return self::$labels[$course->getValue()];
    }
}
