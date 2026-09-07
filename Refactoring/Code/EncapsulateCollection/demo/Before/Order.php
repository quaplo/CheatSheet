<?php

declare(strict_types=1);

namespace Before;

use OrderItem;

/**
 * PŘED: objednávka vydává své pole položek.
 *
 * V PHP se pole při vrácení kopíruje, takže volající do něj nic
 * nepřidá. Problém je jinde a je zákeřnější — viz demo.
 */
final class Order
{
    /** @var list<OrderItem> */
    private array $items = [];

    public function __construct(public readonly string $number)
    {
    }

    public function addItem(OrderItem $item): void
    {
        $this->items[] = $item;
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }
}
