<?php

declare(strict_types=1);

namespace Pricing;

use CoreDomain;

#[CoreDomain('Vyvažuje marži, konkurenceschopnost a důvěru zákazníka.')]
final class DynamicPrice
{
    public function priceFor(string $sku, int $basePriceInCents): int
    {
        return $basePriceInCents;
    }
}
