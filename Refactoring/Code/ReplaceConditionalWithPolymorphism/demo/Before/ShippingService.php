<?php

declare(strict_types=1);

namespace Before;

use Shipment;

/**
 * PŘED: tentýž `match` na typ dopravy na čtyřech místech.
 *
 * Každá metoda se větví podle téhož řetězce. Přidat nového dopravce
 * znamená najít všechna ta místa — a na jedno se zapomene.
 */
final class ShippingService
{
    public function priceInCents(string $carrier, Shipment $shipment): int
    {
        return match ($carrier) {
            'ppl' => $shipment->orderValueInCents >= 250000 ? 0 : 9900,
            'dhl' => 24900 + (int) ceil($shipment->weightInGrams / 1000) * 2000,
            'pickup' => 0,
            default => throw new \InvalidArgumentException('Neznámý dopravce: ' . $carrier),
        };
    }

    public function deliveryDays(string $carrier): int
    {
        return match ($carrier) {
            'ppl' => 2,
            'dhl' => 4,
            'pickup' => 1,
            default => throw new \InvalidArgumentException('Neznámý dopravce: ' . $carrier),
        };
    }

    public function requiresAddress(string $carrier): bool
    {
        return match ($carrier) {
            'ppl', 'dhl' => true,
            'pickup' => false,
            default => throw new \InvalidArgumentException('Neznámý dopravce: ' . $carrier),
        };
    }

    public function label(string $carrier): string
    {
        return match ($carrier) {
            'ppl' => 'PPL — doručení na adresu',
            'dhl' => 'DHL Express',
            'pickup' => 'Osobní odběr na prodejně',
            default => throw new \InvalidArgumentException('Neznámý dopravce: ' . $carrier),
        };
    }
}
