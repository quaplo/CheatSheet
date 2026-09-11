<?php

declare(strict_types=1);

namespace GoodShop\Ordering\Api;

/** Veřejné rozhraní modulu Ordering: to jediné smí volat okolí. */
final readonly class OrderSummary
{
    public function __construct(
        public string $orderId,
        public int $totalInCents,
    ) {
    }
}
