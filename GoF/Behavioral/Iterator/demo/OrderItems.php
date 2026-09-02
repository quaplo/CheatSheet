<?php

declare(strict_types=1);

/**
 * Kolekce, která skrývá svou strukturu.
 *
 * Uvnitř je pole indexované podle SKU — a volajícího to nezajímá,
 * protože `foreach` funguje. Kdyby se z toho zítra stal `SplHeap`
 * nebo databázový kurzor, volající kód se nezmění.
 *
 * V PHP se to dělá přes IteratorAggregate: řekneš, KDO umí procházet,
 * a nemusíš implementovat pět metod ručně.
 *
 * @implements IteratorAggregate<string, OrderItem>
 */
final class OrderItems implements IteratorAggregate, Countable
{
    /** @var array<string, OrderItem> */
    private array $items = [];

    public function add(OrderItem $item): void
    {
        $this->items[$item->sku] = $item;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Filtrovaný průchod jako GENERÁTOR.
     *
     * Nevrací pole — vrací iterátor, který hodnoty vydává postupně.
     * Nad milionem položek je to rozdíl mezi „projde to“ a „spadne
     * to na paměti“.
     *
     * @return Generator<string, OrderItem>
     */
    public function moreExpensiveThan(int $priceInCents): Generator
    {
        foreach ($this->items as $sku => $item) {
            if ($item->priceInCents > $priceInCents) {
                yield $sku => $item;
            }
        }
    }
}
