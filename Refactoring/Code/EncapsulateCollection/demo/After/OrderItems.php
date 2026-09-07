<?php

declare(strict_types=1);

namespace After;

use OrderItem;

/**
 * PO: kolekce jako vlastní typ.
 *
 * Drží pole a k němu pravidla skupiny, která se dřív neměla kde
 * vynutit. Operace, které byly rozeseté po projektu, jsou tady.
 *
 * Implementuje IteratorAggregate a Countable, aby se s ní dalo
 * pracovat jako s polem tam, kde to dává smysl — foreach a count().
 *
 * @implements \IteratorAggregate<int, OrderItem>
 */
final class OrderItems implements \IteratorAggregate, \Countable
{
    public const int MAX_DISTINCT_ITEMS = 10;

    /** @var list<OrderItem> */
    private array $items = [];

    public function add(OrderItem $item): void
    {
        // Pravidla skupiny — dřív nebylo kde je vynutit.
        if (count($this->items) >= self::MAX_DISTINCT_ITEMS) {
            throw new \DomainException(
                sprintf('Objednávka smí mít nejvýš %d různých položek.', self::MAX_DISTINCT_ITEMS),
            );
        }

        foreach ($this->items as $existing) {
            if ($existing->sku === $item->sku) {
                throw new \DomainException(
                    sprintf('SKU %s už v objednávce je; zvyš množství.', $item->sku),
                );
            }
        }

        $this->items[] = $item;
    }

    public function totalInCents(): int
    {
        return array_sum(array_map(
            static fn (OrderItem $i): int => $i->subtotalInCents(),
            $this->items,
        ));
    }

    public function pieceCount(): int
    {
        return array_sum(array_map(
            static fn (OrderItem $i): int => $i->quantity(),
            $this->items,
        ));
    }

    public function moreExpensiveThan(int $priceInCents): self
    {
        $filtered = new self();

        foreach ($this->items as $item) {
            if ($item->priceInCents() > $priceInCents) {
                $filtered->add($item);
            }
        }

        return $filtered;
    }

    /** @return list<string> */
    public function skus(): array
    {
        return array_map(static fn (OrderItem $i): string => $i->sku, $this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }
}
