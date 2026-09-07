<?php

declare(strict_types=1);

namespace TypeCode;

/**
 * Výchozí stav: druh zákazníka jako řetězec v poli.
 *
 * Nic tu zatím nekřičí — velký switch nikde není. Smell je ta
 * hodnota sama: nese chování, ale je to jen text.
 */
final class Customer
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        private string $tier,
        private int $lifetimeValueInCents = 0,
    ) {
    }

    public function discountPercent(): int
    {
        return $this->tier === 'premium' ? 10 : 0;
    }

    public function freeShippingFromInCents(): int
    {
        return $this->tier === 'premium' ? 0 : 100000;
    }

    /** Druh se za života zákazníka mění — a to je celý problém. */
    public function upgradeToPremium(): void
    {
        $this->tier = 'premium';
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
