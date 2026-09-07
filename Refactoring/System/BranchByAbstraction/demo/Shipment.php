<?php

declare(strict_types=1);

/**
 * Zásilka — vstup pro výpočet dopravy.
 */
final readonly class Shipment
{
    public function __construct(
        public string $countryCode,
        public int $weightInGrams,
        public int $orderValueInCents,
    ) {
    }
}
