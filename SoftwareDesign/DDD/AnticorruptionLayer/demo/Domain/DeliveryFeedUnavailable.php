<?php

declare(strict_types=1);

namespace Domain;

/**
 * Naše selhání, ne jejich.
 *
 * Doména neví, co je „ERR X07“ ani co je HTTP 503. Ví jen, že zdroj
 * dodávek teď není dostupný — a to jí stačí k rozhodnutí, co dál.
 */
final class DeliveryFeedUnavailable extends \RuntimeException
{
}
