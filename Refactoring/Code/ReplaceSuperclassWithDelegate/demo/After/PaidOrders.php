<?php

declare(strict_types=1);

namespace After;

/**
 * Tatáž kolekce, ale pole je uvnitř, ne v předkovi.
 *
 * Veřejné je jen to, co jsme napsali. Pravidlo „jen zaplacené"
 * nemá kudy obejít, protože jiná cesta dovnitř neexistuje.
 */
final class PaidOrders implements \Countable, \IteratorAggregate
{
    /** @var list<array{id: int, paid: bool, totalInCents: int}> */
    private array $orders = [];

    /** @param array{id: int, paid: bool, totalInCents: int} $order */
    public function add(array $order): void
    {
        if (!$order['paid']) {
            throw new \InvalidArgumentException('Do PaidOrders patří jen zaplacené objednávky.');
        }

        $this->orders[] = $order;
    }

    public function totalInCents(): int
    {
        $sum = 0;

        foreach ($this->orders as $order) {
            $sum += $order['totalInCents'];
        }

        return $sum;
    }

    public function count(): int
    {
        return count($this->orders);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->orders);
    }
}
