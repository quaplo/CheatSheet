<?php

declare(strict_types=1);

namespace Handlers;

use Domain\OrderPlaced;

/**
 * Reakce v JINÉM agregátu.
 *
 * Sklad je vlastní agregát s vlastními pravidly. Kdyby se měnil ve
 * stejné transakci jako objednávka, porušilo by se pravidlo „jedna
 * transakce = jeden agregát“. Událost je způsob, jak to obejít
 * poctivě: konzistence se dožene za chvíli.
 */
final class ReserveStock
{
    /** @var array<string, int> */
    public array $reserved = [];

    public function __invoke(OrderPlaced $event): void
    {
        foreach ($event->items as $item) {
            $this->reserved[$item['sku']] = ($this->reserved[$item['sku']] ?? 0) + $item['quantity'];

            printf("            → rezervace %s × %d\n", $item['sku'], $item['quantity']);
        }
    }
}
