<?php

declare(strict_types=1);

namespace Ordering;

/** Podpora. */
final readonly class OrderLine
{
    public function __construct(
        public string $sku,
        public int $quantity,
    ) {
    }
}
