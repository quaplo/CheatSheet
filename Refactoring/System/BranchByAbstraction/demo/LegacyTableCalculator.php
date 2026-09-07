<?php

declare(strict_types=1);

/**
 * STARÁ implementace: pevná tabulka sazeb.
 *
 * Funguje roky, nikdo jí nerozumí a rozšířit ji o novou zemi znamená
 * dopsat řádek do pole. Přesně ten typ kódu, který se vyměňuje.
 */
final class LegacyTableCalculator implements ShippingCalculator
{
    /** @var array<string, array{carrier: string, price: int, days: int}> */
    private const array RATES = [
        'CZ' => ['carrier' => 'PPL',  'price' => 9900,  'days' => 2],
        'SK' => ['carrier' => 'PPL',  'price' => 14900, 'days' => 3],
        'DE' => ['carrier' => 'DHL',  'price' => 24900, 'days' => 4],
    ];

    private const int FREE_SHIPPING_FROM = 250000;

    public function quoteFor(Shipment $shipment): ShippingQuote
    {
        $rate = self::RATES[$shipment->countryCode] ?? self::RATES['DE'];

        $price = $shipment->orderValueInCents >= self::FREE_SHIPPING_FROM
            ? 0
            : $rate['price'];

        return new ShippingQuote($rate['carrier'], $price, $rate['days']);
    }
}
