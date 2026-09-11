<?php

declare(strict_types=1);

namespace GoodShop\Shipping\Application;

use GoodShop\Ordering\Api\Orders;
use GoodShop\Catalog\Api\Products;

final class LabelPrinter
{
    public function __construct(
        private readonly Orders $orders,
        private readonly Products $products,
    ) {
    }

    public function print(string $orderId, string $sku): string
    {
        return $this->orders->summaryOf($orderId)->orderId . ' / ' . $sku;
    }
}
