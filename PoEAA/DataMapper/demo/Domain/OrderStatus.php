<?php

declare(strict_types=1);

namespace Domain;

/** V jednom schématu je to číslo, ve druhém řetězec. Doména zná jen tohle. */
enum OrderStatus: string
{
    case New = 'nová';
    case Paid = 'zaplacená';
    case Shipped = 'odeslaná';
}
