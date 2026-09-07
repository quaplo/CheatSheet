<?php

declare(strict_types=1);

final class OrderReport
{
    public function __construct(private readonly int $vatPercent)
    {
    }

    public function summary(int $amountInCents): string
    {
        return 'Objednávka za ' . (new OrderService())->total($amountInCents) . ' haléřů';
    }
}
