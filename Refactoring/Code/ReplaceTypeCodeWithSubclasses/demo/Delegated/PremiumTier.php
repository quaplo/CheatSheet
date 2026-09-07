<?php

declare(strict_types=1);

namespace Delegated;

final class PremiumTier implements CustomerTier
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
