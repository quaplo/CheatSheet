<?php

declare(strict_types=1);

namespace Before;

/**
 * Kolekce zaplacených objednávek — děděná z ArrayObject.
 *
 * Vypadá to úsporně: `count()`, iterace i přístup přes hranaté
 * závorky jsou zadarmo. Cenou je, že s nimi třída zdědila i všechno
 * ostatní, čím se dá její pravidlo obejít.
 *
 * @extends \ArrayObject<int, array{id: int, paid: bool, totalInCents: int}>
 */
final class PaidOrders extends \ArrayObject
{
    /** @param array{id: int, paid: bool, totalInCents: int} $order */
    public function add(array $order): void
    {
        if (!$order['paid']) {
            throw new \InvalidArgumentException('Do PaidOrders patří jen zaplacené objednávky.');
        }

        $this->append($order);
    }

    public function totalInCents(): int
    {
        $sum = 0;

        foreach ($this as $order) {
            $sum += $order['totalInCents'];
        }

        return $sum;
    }
}
