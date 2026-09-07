<?php

declare(strict_types=1);

final class PriceCalculator
{
    public function __construct(private readonly int $vatPercent)
    {
    }

    public function withVat(int $amountInCents): int
    {
        return (int) round($amountInCents * (100 + $this->vatPercent) / 100);
    }
}
