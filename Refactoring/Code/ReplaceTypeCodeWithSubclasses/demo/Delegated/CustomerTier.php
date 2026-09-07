<?php

declare(strict_types=1);

namespace Delegated;

/** Druh zákazníka jako objekt, který se dá vyměnit za běhu. */
interface CustomerTier
{
    public function discountPercent(): int;

    public function freeShippingFromInCents(): int;
}
