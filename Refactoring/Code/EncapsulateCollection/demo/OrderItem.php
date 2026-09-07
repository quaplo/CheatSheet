<?php

declare(strict_types=1);

/**
 * Položka objednávky.
 *
 * Schválně měnitelná — právě na ní je vidět, že kopie pole nestačí.
 */
final class OrderItem
{
    public function __construct(
        public readonly string $sku,
        private int $priceInCents,
        private int $quantity,
    ) {
    }

    public function priceInCents(): int
    {
        return $this->priceInCents;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function subtotalInCents(): int
    {
        return $this->priceInCents * $this->quantity;
    }

    public function changePrice(int $priceInCents): void
    {
        $this->priceInCents = $priceInCents;
    }
}
