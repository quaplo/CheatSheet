<?php

declare(strict_types=1);

final class PriceCalculator
{
    public function withVat(int $amountInCents): int
    {
        return (int) round($amountInCents * (100 + Config::vatPercent()) / 100);
    }
}
