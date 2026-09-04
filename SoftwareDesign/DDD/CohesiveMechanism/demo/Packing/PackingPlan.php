<?php

declare(strict_types=1);

namespace Packing;

/**
 * Výsledek balení — co přišlo do které krabice.
 *
 * Mechanismus vrací plán, ne rozhodnutí. Co s ním doména udělá,
 * není jeho starost.
 */
final readonly class PackingPlan
{
    /** @param list<PackedBox> $boxes */
    public function __construct(public array $boxes)
    {
    }

    public function boxCount(): int
    {
        return count($this->boxes);
    }

    public function totalPriceInCents(): int
    {
        return array_sum(array_map(
            static fn (PackedBox $box): int => $box->size->priceInCents,
            $this->boxes,
        ));
    }
}
