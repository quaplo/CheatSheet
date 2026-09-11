<?php

declare(strict_types=1);

/**
 * Pravidlo pro dopravu zdarma.
 *
 * Není součástí událostí — je součástí KÓDU, který je přehrává.
 * A právě proto se s ním dá historie nechtěně přepsat.
 */
final readonly class ShippingRules
{
    public function __construct(
        public int $freeFromInCents,
        public int $priceInCents = 9900,
    ) {
    }

    public function shippingFor(int $itemsTotalInCents): int
    {
        return $itemsTotalInCents >= $this->freeFromInCents ? 0 : $this->priceInCents;
    }
}
