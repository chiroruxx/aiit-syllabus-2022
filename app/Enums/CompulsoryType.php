<?php

declare(strict_types=1);

namespace App\Enums;

enum CompulsoryType: int
{
    case COMPULSORY = 0;
    case SELECTABLE = 1;
    case SELECTABLE_COMPULSORY = 2;

    public static function label(self $compulsory): string
    {
        return match ($compulsory) {
            self::COMPULSORY => '必修',
            self::SELECTABLE => '選択',
            self::SELECTABLE_COMPULSORY => '必修選択',
        };
    }
}
