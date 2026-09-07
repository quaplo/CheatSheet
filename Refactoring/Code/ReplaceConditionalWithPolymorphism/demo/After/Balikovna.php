<?php

declare(strict_types=1);

namespace After;

use Shipment;

/**
 * NOVÝ dopravce přidaný po refaktoringu.
 *
 * Vznikl jako jeden soubor. Do žádné existující třídy se nesahalo —
 * a PHP nedovolilo zapomenout na jedinou metodu.
 */
final class Balikovna extends ShippingMethod
{
    public function code(): string
    {
        return 'balikovna';
    }

    public function priceInCents(Shipment $shipment): int
    {
        return $shipment->weightInGrams > 10000 ? 12900 : 6900;
    }

    public function deliveryDays(): int
    {
        return 3;
    }

    public function requiresAddress(): bool
    {
        return false;
    }

    public function label(): string
    {
        return 'Balíkovna — výdejní místo';
    }
}
