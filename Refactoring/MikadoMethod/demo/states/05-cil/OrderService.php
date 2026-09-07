<?php

declare(strict_types=1);

final class OrderService
{
    public function __construct(private readonly int $vatPercent)
    {
    }

    public function total(int $amountInCents): int
    {
        return (new PriceCalculator($this->vatPercent))->withVat($amountInCents);
    }
}
