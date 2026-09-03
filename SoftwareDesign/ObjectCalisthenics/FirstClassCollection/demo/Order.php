<?php

declare(strict_types=1);

/**
 * Objednávka. Nedrží pole položek, ale kolekci — a proto se sama nemusí
 * starat o to, jak se počítá cena nebo hmotnost.
 */
final readonly class Order
{
    public function __construct(
        public string $number,
        public OrderItems $items,
    ) {
    }

    public static function empty(string $number): self
    {
        return new self($number, OrderItems::empty());
    }

    public function withItem(OrderItem $item): self
    {
        return new self($this->number, $this->items->withItem($item));
    }

    public function total(): int
    {
        return $this->items->total();
    }
}
