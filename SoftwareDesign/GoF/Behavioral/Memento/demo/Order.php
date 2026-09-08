<?php

declare(strict_types=1);

/**
 * Originátor: objekt, jehož stav se snímkuje.
 *
 * Snímek si vyrábí i obnovuje sám — nikdo zvenčí nesahá na jeho
 * vnitřek a nemusí vědět, z čeho se stav skládá.
 */
final class Order
{
    /** @var list<OrderItem> */
    private array $items = [];

    private string $status = 'new';

    private int $discountPercent = 0;

    public function addItem(string $sku, int $quantity): void
    {
        $this->items[] = new OrderItem($sku, $quantity);
    }

    public function applyDiscount(int $percent): void
    {
        $this->discountPercent = $percent;
    }

    public function confirm(): void
    {
        $this->status = 'confirmed';
    }

    /** Hluboká kopie: snímek nesmí sdílet měnitelné objekty s originálem. */
    public function save(): Memento
    {
        return new OrderMemento(
            array_map(
                static fn (OrderItem $item): OrderItem => new OrderItem($item->sku, $item->quantity),
                $this->items,
            ),
            $this->status,
            $this->discountPercent,
        );
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

    public function describe(): string
    {
        return sprintf(
            '%s, %d ks, sleva %d %%',
            $this->status,
            $this->itemCount(),
            $this->discountPercent,
        );
    }
}
