<?php

declare(strict_types=1);

namespace Delegated;

final class StandardTier implements CustomerTier
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
