<?php

declare(strict_types=1);

namespace Subclasses;

/** Druh zákazníka jako podtřída. */
abstract class Customer
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        protected int $lifetimeValueInCents = 0,
    ) {
    }

    abstract public function discountPercent(): int;

    abstract public function freeShippingFromInCents(): int;

    public function addPurchase(int $amountInCents): void
    {
        $this->lifetimeValueInCents += $amountInCents;
    }

    public function lifetimeValueInCents(): int
    {
        return $this->lifetimeValueInCents;
    }
}
