<?php

declare(strict_types=1);

namespace GoodShop\Catalog\Application;

use GoodShop\Ordering\Api\Orders;

/**
 * Totéž, ale přes veřejné rozhraní modulu Ordering.
 *
 * Nezná Order ani OrderLine — zná jen to, co Ordering nabízí ven.
 */
final class RecommendationService
{
    public function __construct(private readonly Orders $orders)
    {
    }

    public function skusFrom(string $orderId): array
    {
        return [$this->orders->summaryOf($orderId)->orderId];
    }
}
