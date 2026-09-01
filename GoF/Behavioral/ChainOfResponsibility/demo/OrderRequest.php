<?php

declare(strict_types=1);

/** Objednávka putující zpracovatelským řetězem. */
final readonly class OrderRequest
{
    public function __construct(
        public string $orderNumber,
        public int $totalInCents,
        public int $itemCount,
        public bool $inStock,
    ) {
    }
}
