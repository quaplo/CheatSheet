<?php

declare(strict_types=1);

/**
 * Kolekce položek objednávky jako plnohodnotný doménový objekt.
 *
 * Tři věci, kvůli kterým tahle třída existuje:
 *
 *  1. Uvnitř může být jen OrderItem — nic jiného sem neprojde.
 *  2. Pravidla o skupině položek (limit, součty) jsou na jednom místě,
 *     ne rozeseté po use-caseech.
 *  3. Kolekce je neměnná — každá úprava vrací novou instanci, takže ji
 *     jde bezpečně sdílet.
 *
 * @implements IteratorAggregate<int, OrderItem>
 */
final readonly class OrderItems implements IteratorAggregate, Countable
{
    private const int MAX_ITEMS = 20;

    /** @var list<OrderItem> */
    private array $items;

    /**
     * Konstruktor je privátní schválně — instance vznikají přes pojmenované
     * továrny, takže je z volajícího kódu vidět, co se děje.
     *
     * @param list<OrderItem> $items
     */
    private function __construct(array $items)
    {
        if (count($items) > self::MAX_ITEMS) {
            throw new InvalidArgumentException(
                sprintf('Objednávka smí mít nejvýš %d položek, dostal jsem %d.', self::MAX_ITEMS, count($items)),
            );
        }

        $this->items = array_values($items);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param list<OrderItem> $items
     */
    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    public function withItem(OrderItem $item): self
    {
        return new self([...$this->items, $item]);
    }

    /**
     * Hromadné přidání. Použij ho vždy, když položky přidáváš ve smyčce —
     * withItem() volaný N× kopíruje pole N× a chová se kvadraticky.
     *
     * @param list<OrderItem> $items
     */
    public function withItems(array $items): self
    {
        return new self([...$this->items, ...$items]);
    }

    public function withoutProduct(string $productName): self
    {
        return new self(array_values(array_filter(
            $this->items,
            static fn (OrderItem $item): bool => $item->productName !== $productName,
        )));
    }

    /** Cena všech položek v haléřích. */
    public function total(): int
    {
        return array_sum(array_map(
            static fn (OrderItem $item): int => $item->total(),
            $this->items,
        ));
    }

    /** Hmotnost všech položek v gramech. */
    public function totalWeight(): int
    {
        return array_sum(array_map(
            static fn (OrderItem $item): int => $item->weight(),
            $this->items,
        ));
    }

    /**
     * Filtrování vrací zase OrderItems, ne pole — na výsledku jde volat
     * total() a řetězit další operace.
     */
    public function heavierThan(int $grams): self
    {
        return new self(array_values(array_filter(
            $this->items,
            static fn (OrderItem $item): bool => $item->weight() > $grams,
        )));
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
