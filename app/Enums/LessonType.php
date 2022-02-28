<?php

declare(strict_types=1);

namespace App\Enums;

use MyCLabs\Enum\Enum;

class LessonType extends Enum
{
    public const IN_PERSON = 0;
    public const ON_DEMAND = 1;
    public const HIGH_FLEX = 2;
    public const OTHER = 3;
}
