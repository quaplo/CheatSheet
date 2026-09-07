<?php

declare(strict_types=1);

namespace After;

use Shipment;

final class Pickup extends ShippingMethod
{
    public function code(): string
    {
        return 'pickup';
    }

    public function priceInCents(Shipment $shipment): int
    {
        return 0;
    }

    public function deliveryDays(): int
    {
        return 1;
    }

    public function requiresAddress(): bool
    {
        return false;
    }

    public function label(): string
    {
        return 'Osobní odběr na prodejně';
    }
}
