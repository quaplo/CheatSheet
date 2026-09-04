<?php

declare(strict_types=1);

/**
 * Rozměr krabice — doménová hodnota.
 */
final readonly class BoxSize
{
    public function __construct(
        public string $name,
        public int $capacityInMillilitres,
        public int $priceInCents,
    ) {
    }
}
