<?php

declare(strict_types=1);

/** Objednávka má alespoň N položek. */
final class OrderHasAtLeastItems extends OrderSpecification
{
    public function __construct(
        private readonly int $minimum,
    ) {
    }

    public function isSatisfiedBy(Order $order): bool
    {
        return $order->itemCount >= $this->minimum;
    }

    public function describe(): string
    {
        return sprintf('obsahuje alespoň %d položek', $this->minimum);
    }
}
