<?php

declare(strict_types=1);

namespace Step2_Prepared;

interface ShippingMethod
{
    public function code(): string;

    public function priceInCents(int $weightInGrams, int $orderValueInCents): int;
}
