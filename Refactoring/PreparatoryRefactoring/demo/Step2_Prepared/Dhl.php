<?php

declare(strict_types=1);

namespace Step2_Prepared;

final class Dhl implements ShippingMethod
{
    public function code(): string
    {
        return 'dhl';
    }

    public function priceInCents(int $weightInGrams, int $orderValueInCents): int
    {
        $kilos = (int) ceil($weightInGrams / 1000);

        return 24900 + max(0, $kilos - 1) * 2000;
    }
}
