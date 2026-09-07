<?php

declare(strict_types=1);

namespace Subclasses;

final class PremiumCustomer extends Customer
{
    public function discountPercent(): int
    {
        return 10;
    }

    public function freeShippingFromInCents(): int
    {
        return 0;
    }
}
