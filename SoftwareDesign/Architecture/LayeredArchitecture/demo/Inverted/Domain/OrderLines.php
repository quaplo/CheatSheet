<?php

declare(strict_types=1);

namespace Inverted\Domain;

/**
 * Rozhraní, které si definuje DOMÉNA.
 *
 * Tím se otočila šipka: infrastruktura teď závisí na doméně,
 * ne naopak. Vrstvy zůstaly, jen infrastruktura přestala být „níž".
 */
interface OrderLines
{
    /** @return list<OrderLine> */
    public function of(string $orderId): array;
}
