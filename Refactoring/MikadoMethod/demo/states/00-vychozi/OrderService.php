<?php

declare(strict_types=1);

final class OrderService
{
    public function total(int $amountInCents): int
    {
        return (new PriceCalculator())->withVat($amountInCents);
    }
}
