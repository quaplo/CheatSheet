<?php

declare(strict_types=1);

/**
 * Objednávka. Kvůli jednoduchosti drží jen to, co potřebuje výpočet dopravy.
 *
 * Peněžní částky jsou v haléřích (int), aby se nepočítalo s float.
 */
final readonly class Order
{
    public function __construct(
        public string $number,
        public int $totalInCents,
        public int $weightInGrams,
        public string $countryCode,
    ) {
    }
}
