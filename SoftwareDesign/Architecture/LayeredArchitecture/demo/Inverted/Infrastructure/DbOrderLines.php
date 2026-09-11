<?php

declare(strict_types=1);

namespace Inverted\Infrastructure;

use Inverted\Domain\OrderLine;
use Inverted\Domain\OrderLines;

/** Infrastruktura naplňuje rozhraní, které si určila doména. */
final class DbOrderLines implements OrderLines
{
    /** @return list<OrderLine> */
    public function of(string $orderId): array
    {
        return [new OrderLine(12900, 2), new OrderLine(4900, 1)];
    }
}
