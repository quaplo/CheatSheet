<?php

declare(strict_types=1);

/**
 * NOVÁ implementace: sazba podle hmotnosti a zóny.
 *
 * Vzniká ZA abstrakcí, zatímco stará dál obsluhuje provoz.
 *
 * Fowler: „Create a new supplier that implements the required features
 * through the same abstraction layer."
 */
final class WeightBasedCalculator implements ShippingCalculator
{
    /** @var array<string, array{carrier: string, base: int, perKilo: int, days: int}> */
    private const array ZONES = [
        'CZ' => ['carrier' => 'PPL', 'base' => 7900,  'perKilo' => 1000, 'days' => 2],
        'SK' => ['carrier' => 'PPL', 'base' => 12900, 'perKilo' => 1200, 'days' => 3],
        'DE' => ['carrier' => 'DHL', 'base' => 19900, 'perKilo' => 2500, 'days' => 4],
    ];

    private const int FREE_SHIPPING_FROM = 250000;

    public function quoteFor(Shipment $shipment): ShippingQuote
    {
        $zone = self::ZONES[$shipment->countryCode] ?? self::ZONES['DE'];

        if ($shipment->orderValueInCents >= self::FREE_SHIPPING_FROM) {
            return new ShippingQuote($zone['carrier'], 0, $zone['days']);
        }

        $kilos = (int) ceil($shipment->weightInGrams / 1000);
        $price = $zone['base'] + max(0, $kilos - 1) * $zone['perKilo'];

        return new ShippingQuote($zone['carrier'], $price, $zone['days']);
    }
}
