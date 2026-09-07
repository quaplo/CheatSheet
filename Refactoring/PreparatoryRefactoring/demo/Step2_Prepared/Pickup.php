<?php

declare(strict_types=1);

namespace Step2_Prepared;

final class Pickup implements ShippingMethod
{
    public function code(): string
    {
        return 'pickup';
    }

    public function priceInCents(int $weightInGrams, int $orderValueInCents): int
    {
        return 0;
    }
}
