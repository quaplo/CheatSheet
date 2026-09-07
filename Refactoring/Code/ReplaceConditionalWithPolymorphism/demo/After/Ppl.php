<?php

declare(strict_types=1);

namespace After;

use Shipment;

final class Ppl extends ShippingMethod
{
    private const int FREE_FROM = 250000;

    public function code(): string
    {
        return 'ppl';
    }

    public function priceInCents(Shipment $shipment): int
    {
        return $shipment->orderValueInCents >= self::FREE_FROM ? 0 : 9900;
    }

    public function deliveryDays(): int
    {
        return 2;
    }

    public function requiresAddress(): bool
    {
        return true;
    }

    public function label(): string
    {
        return 'PPL — doručení na adresu';
    }
}
