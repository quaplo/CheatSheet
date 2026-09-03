<?php

declare(strict_types=1);

namespace Domain;

/** Naše stavy. Že jim v ERP odpovídají kódy '01'–'99', je věc překladu. */
enum DeliveryStatus: string
{
    case Announced = 'ohlášená';
    case InTransit = 'na cestě';
    case Received = 'převzatá';
    case Cancelled = 'zrušená';
}
