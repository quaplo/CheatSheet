<?php

declare(strict_types=1);

namespace After\Core;

enum OrderStatus: string
{
    case New = 'nová';
    case Confirmed = 'potvrzená';
    case Cancelled = 'zrušená';
}
