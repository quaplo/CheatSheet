<?php

declare(strict_types=1);

/**
 * Objednávka. Drží jen to, na co se specifikace ptají.
 *
 * Částky jsou v haléřích (int). V reálném kódu by tu bylo Money —
 * viz pattern Value Object; tady zůstávají holé, aby demo zůstalo
 * soustředěné na specifikace.
 */
final readonly class Order
{
    public function __construct(
        public string $number,
        public int $totalInCents,
        public int $itemCount,
        public bool $isPaid,
        public string $countryCode,
    ) {
    }
}
