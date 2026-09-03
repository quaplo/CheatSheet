<?php

declare(strict_types=1);

/** Pozorovatel: zaznamenává úplně všechno. */
final class StockAuditLog implements StockObserver
{
    /** @var list<string> */
    public array $entries = [];

    public function stockChanged(StockItem $item, int $previousQuantity): void
    {
        $this->entries[] = sprintf('%s: %d → %d', $item->sku, $previousQuantity, $item->quantity());
    }
}
