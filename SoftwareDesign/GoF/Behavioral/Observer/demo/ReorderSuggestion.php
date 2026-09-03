<?php

declare(strict_types=1);

/**
 * Pozorovatel, který schválně selhává.
 *
 * Ukazuje nejnepříjemnější vlastnost synchronního Observeru:
 * bez ošetření shodí jedna vadná reakce i ty ostatní — a hlavně
 * tu původní operaci, která s ní nemá nic společného.
 */
final class ReorderSuggestion implements StockObserver
{
    public bool $failNext = false;

    public function stockChanged(StockItem $item, int $previousQuantity): void
    {
        if ($this->failNext) {
            $this->failNext = false;

            throw new RuntimeException('Doplňovací systém neodpovídá.');
        }

        if ($item->quantity() === 0) {
            printf("            → objednat %s u dodavatele\n", $item->sku);
        }
    }
}
