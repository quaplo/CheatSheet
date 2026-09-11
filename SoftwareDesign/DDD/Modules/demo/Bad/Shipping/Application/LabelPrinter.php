<?php

declare(strict_types=1);

namespace BadShop\Shipping\Application;

use BadShop\Ordering\Domain\Order;
use BadShop\Catalog\Domain\Product;

/** Tisk štítku. Taky si bere cizí vnitřek. */
final class LabelPrinter
{
    public function print(Order $order, Product $product): string
    {
        return $order->id . ' / ' . $product->sku;
    }
}
