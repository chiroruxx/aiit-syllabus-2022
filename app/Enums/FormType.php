<?php

declare(strict_types=1);

namespace App\Enums;

enum FormType: int
{
    case FORM_TYPE_IN_PERSON = 0;
    case FORM_TYPE_HIGH_FLEX = 1;
    case FORM_TYPE_ON_DEMAND = 2;
    case FORM_TYPE_OTHER = 3;

    public static function label(self $course): string
    {
        return match ($course) {
            self::FORM_TYPE_IN_PERSON => '対面',
            self::FORM_TYPE_HIGH_FLEX => 'ハイフレックス',
            self::FORM_TYPE_ON_DEMAND => '録画視聴',
            self::FORM_TYPE_OTHER => 'その他',
        };
    }
}
