<?php

declare(strict_types=1);

namespace Before;

use OrderItem;

/**
 * Kód, který s položkami pracuje — rozeseté na pěti místech.
 *
 * Každá z těchhle funkcí je jinde v projektu: kontroler, šablona,
 * exportér, e-mail, report. Všechny dělají totéž nad týmž polem.
 */
final class Usage
{
    public static function total(Order $order): int
    {
        return array_sum(array_map(
            static fn (OrderItem $i): int => $i->subtotalInCents(),
            $order->items(),
        ));
    }

    public static function itemCount(Order $order): int
    {
        return array_sum(array_map(
            static fn (OrderItem $i): int => $i->quantity(),
            $order->items(),
        ));
    }

    public static function expensiveItems(Order $order, int $threshold): array
    {
        return array_values(array_filter(
            $order->items(),
            static fn (OrderItem $i): bool => $i->priceInCents() > $threshold,
        ));
    }

    public static function skus(Order $order): array
    {
        return array_map(static fn (OrderItem $i): string => $i->sku, $order->items());
    }

    public static function hasItems(Order $order): bool
    {
        return count($order->items()) > 0;
    }
}
