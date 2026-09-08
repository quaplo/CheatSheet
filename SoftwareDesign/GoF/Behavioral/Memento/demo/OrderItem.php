<?php

declare(strict_types=1);

/** Položka objednávky. Měnitelná — a právě proto je zajímavá. */
final class OrderItem
{
    public function __construct(
        public readonly string $sku,
        public int $quantity,
    ) {
    }
}
