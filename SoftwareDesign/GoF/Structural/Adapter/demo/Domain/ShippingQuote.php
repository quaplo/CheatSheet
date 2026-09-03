<?php

declare(strict_types=1);

namespace Domain;

/** Nabídka dopravy v NAŠICH pojmech. */
final readonly class ShippingQuote
{
    public function __construct(
        public string $carrier,
        public int $priceInCents,
        public int $deliveryDays,
    ) {
    }
}
