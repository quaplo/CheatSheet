<?php

declare(strict_types=1);

namespace Packing;

use BoxSize;
use PackableItem;

final readonly class PackedBox
{
    /** @param list<PackableItem> $items */
    public function __construct(
        public BoxSize $size,
        public array $items,
    ) {
    }

    public function usedMillilitres(): int
    {
        return array_sum(array_map(
            static fn (PackableItem $item): int => $item->volumeInMillilitres,
            $this->items,
        ));
    }

    public function utilisationPercent(): float
    {
        return $this->usedMillilitres() / $this->size->capacityInMillilitres * 100;
    }
}
