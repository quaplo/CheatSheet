<?php

declare(strict_types=1);

namespace Leaky;

/**
 * Druhá cesta dovnitř, napsaná o půl roku později jiným člověkem.
 *
 * Import z CSV. Autor o pravidle v PlaceOrderHandler nevěděl —
 * a neměl jak vědět, protože není v doméně, ale v cizím use-case.
 *
 * Tohle není nedbalost. Je to nevyhnutelný důsledek toho, kde
 * pravidlo bydlí.
 */
final class ImportOrdersHandler
{
    /** @var list<LooseOrder> */
    public array $saved = [];

    /** @param list<array{customerId: string, total: int}> $rows */
    public function handle(array $rows): void
    {
        foreach ($rows as $row) {
            $this->saved[] = LooseOrder::place('IMP-' . count($this->saved), $row['customerId'], $row['total']);
        }
    }
}
