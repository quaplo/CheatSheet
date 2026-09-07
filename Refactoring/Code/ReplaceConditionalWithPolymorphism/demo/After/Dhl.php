<?php

declare(strict_types=1);

namespace After;

use Shipment;

final class Dhl extends ShippingMethod
{
    public function code(): string
    {
        return 'dhl';
    }

    public function priceInCents(Shipment $shipment): int
    {
        return 24900 + (int) ceil($shipment->weightInGrams / 1000) * 2000;
    }

    public function deliveryDays(): int
    {
        return 4;
    }

    public function requiresAddress(): bool
    {
        return true;
    }

    public function label(): string
    {
        return 'DHL Express';
    }
}
