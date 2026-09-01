<?php

declare(strict_types=1);

/**
 * Měnitelná varianta kolekce — pro případy, kdy se kolekce plní postupně
 * a je uvnitř jedné operace, ne sdílená napříč aplikací.
 *
 * Všechno ostatní zůstává stejné jako u neměnné varianty: vlastní jméno,
 * typová homogenita, invariant na jednom místě, doménové metody. Liší se
 * jediná věc — add() mění tuhle instanci místo toho, aby vracel novou.
 *
 * Cenou je, že takovou kolekci nesmíš bez rozmyslu předávat dál: kdokoli
 * ji dostane, může ti ji změnit pod rukama.
 *
 * @implements IteratorAggregate<int, OrderItem>
 */
final class MutableOrderItems implements IteratorAggregate, Countable
{
    private const int MAX_ITEMS = 20;

    /** @var list<OrderItem> */
    private array $items = [];

    public function add(OrderItem $item): void
    {
        if (count($this->items) + 1 > self::MAX_ITEMS) {
            throw new InvalidArgumentException(
                sprintf('Objednávka smí mít nejvýš %d položek.', self::MAX_ITEMS),
            );
        }

        $this->items[] = $item;
    }

    /** Uzavření sběru: z měnitelné kolekce udělej neměnnou a tu pošli dál. */
    public function toImmutable(): OrderItems
    {
        return OrderItems::fromArray($this->items);
    }

    public function total(): int
    {
        return array_sum(array_map(
            static fn (OrderItem $item): int => $item->total(),
            $this->items,
        ));
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
