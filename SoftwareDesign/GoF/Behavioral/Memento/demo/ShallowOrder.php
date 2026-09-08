<?php

declare(strict_types=1);

/**
 * Táž objednávka, ale se snímkem udělaným mělce.
 *
 * `array_map` chybí, takže do snímku jde totéž pole objektů, které
 * drží originál. Vypadá to správně a v testu s neměnnými hodnotami
 * to i projde.
 */
final class ShallowOrder
{
    /** @var list<OrderItem> */
    private array $items = [];

    private string $status = 'new';

    private int $discountPercent = 0;

    public function addItem(string $sku, int $quantity): void
    {
        $this->items[] = new OrderItem($sku, $quantity);
    }

    public function changeQuantity(int $index, int $quantity): void
    {
        $this->items[$index]->quantity = $quantity;
    }

    public function save(): Memento
    {
        return new OrderMemento($this->items, $this->status, $this->discountPercent);
    }

    public function restore(Memento $memento): void
    {
        if (!$memento instanceof OrderMemento) {
            throw new InvalidArgumentException('Tenhle snímek nepatří objednávce.');
        }

        $this->items = $memento->items;
        $this->status = $memento->status;
        $this->discountPercent = $memento->discountPercent;
    }

    public function itemCount(): int
    {
        return array_sum(array_map(static fn (OrderItem $item): int => $item->quantity, $this->items));
    }
}
