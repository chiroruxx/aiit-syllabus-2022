<?php

declare(strict_types=1);

namespace App\Enums;

enum LessonType: int
{
    case IN_PERSON = 0;
    case ON_DEMAND = 1;
    case HIGH_FLEX = 2;
    case OTHER = 3;
}
