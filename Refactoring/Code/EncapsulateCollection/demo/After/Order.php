<?php

declare(strict_types=1);

namespace After;

use OrderItem;

/**
 * PO: objednávka drží kolekci, ne pole.
 *
 * Metodu items(): array už nemá — a to je celý smysl. Kdo potřebuje
 * součet, zeptá se kolekce; kdo potřebuje projít položky, použije
 * foreach, protože kolekce je iterovatelná.
 */
final class Order
{
    private OrderItems $items;

    public function __construct(public readonly string $number)
    {
        $this->items = new OrderItems();
    }

    public function addItem(OrderItem $item): void
    {
        $this->items->add($item);
    }

    public function items(): OrderItems
    {
        return $this->items;
    }
}
