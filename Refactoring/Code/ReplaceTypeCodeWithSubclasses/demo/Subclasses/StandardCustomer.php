<?php

declare(strict_types=1);

namespace Subclasses;

final class StandardCustomer extends Customer
{
    public function discountPercent(): int
    {
        return 0;
    }

    public function freeShippingFromInCents(): int
    {
        return 100000;
    }
}
