<?php

declare(strict_types=1);

namespace Delegated;

/**
 * Zákazník, jehož druh je objekt uvnitř.
 *
 * Povýšení je výměna jednoho pole. Objekt zůstává týž, takže
 * všechny reference na něj vidí novou skutečnost.
 */
final class Customer
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        private CustomerTier $tier,
        private int $lifetimeValueInCents = 0,
    ) {
    }

    public function discountPercent(): int
    {
        return $this->tier->discountPercent();
    }

    public function freeShippingFromInCents(): int
    {
        return $this->tier->freeShippingFromInCents();
    }

    public function upgradeTo(CustomerTier $tier): void
    {
        $this->tier = $tier;
    }

    public function addPurchase(int $amountInCents): void
    {
        $this->lifetimeValueInCents += $amountInCents;
    }

    public function lifetimeValueInCents(): int
    {
        return $this->lifetimeValueInCents;
    }
}
