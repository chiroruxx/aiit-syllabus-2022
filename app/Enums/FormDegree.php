<?php

declare(strict_types=1);

namespace App\Enums;

enum FormDegree: int
{
    case NONE = 0;
    case SOMETIMES = 1;
    case OFTEN = 2;
}
