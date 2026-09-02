<?php

declare(strict_types=1);

/** Pozorovatel: hlídá podlimitní zásobu. */
final class LowStockAlert implements StockObserver
{
    /** @var list<string> */
    public array $alerts = [];

    public function __construct(
        private readonly int $threshold = 10,
    ) {
    }

    public function stockChanged(StockItem $item, int $previousQuantity): void
    {
        // Reaguje jen na PŘEKROČENÍ hranice, ne na každou změnu.
        if ($previousQuantity > $this->threshold && $item->quantity() <= $this->threshold) {
            $this->alerts[] = $item->sku;

            printf("            ⚠ %s klesl na %d ks (limit %d)\n", $item->sku, $item->quantity(), $this->threshold);
        }
    }
}
