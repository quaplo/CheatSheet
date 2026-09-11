<?php

declare(strict_types=1);

namespace BadShop\Catalog\Application;

use BadShop\Ordering\Domain\Order;

/**
 * Doporučení podle toho, co si zákazník objednal.
 *
 * Sahá rovnou do vnitřku modulu Ordering — v PHP to projde
 * a nikdo se to nedozví.
 */
final class RecommendationService
{
    public function skusFrom(Order $order): array
    {
        return [$order->id];
    }
}
