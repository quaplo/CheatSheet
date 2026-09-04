<?php

declare(strict_types=1);

namespace After;

/** Rezervace — blokace skladové zásoby pro konkrétní objednávku. */
final class Reservation
{
    public function __construct(
        public readonly string $sku,
        public readonly int $quantity,
    ) {
    }
}
