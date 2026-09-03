<?php

declare(strict_types=1);

/**
 * PŘÍKAZ. Popisuje záměr, ne data k zobrazení.
 *
 * Příkaz se jmenuje slovesem v rozkazovacím způsobu — PlaceOrder,
 * nikoli OrderData. To není kosmetika: donutí tě to pojmenovat, co se
 * má stát, místo abys posílal beztvarý balík polí.
 */
final readonly class PlaceOrder
{
    /** @param list<OrderItem> $items */
    public function __construct(
        public string $customerEmail,
        public array $items,
    ) {
    }
}
