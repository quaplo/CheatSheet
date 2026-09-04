<?php

declare(strict_types=1);

namespace Invoicing;

/** Obecná podoblast: sazby DPH určuje zákon, ne naše doména. */
final readonly class VatRate
{
    public function __construct(public float $rate)
    {
    }
}
