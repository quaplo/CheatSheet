<?php

declare(strict_types=1);

namespace Adapter;

use Domain\ShippingProvider;
use Domain\ShippingQuote;
use Vendor\GlobalShipClient;

/**
 * ADAPTÉR pro druhého dopravce — týž kontrakt, úplně jiný překlad.
 *
 *   gramy   → unce
 *   USD     → CZK (potřebuje kurz, který cizí knihovna nezná)
 *   hodiny  → dny
 *
 * Kurz je závislost, kterou má adaptér a doména ne. To je v pořádku:
 * je to detail integrace, ne doménové pravidlo.
 */
final readonly class GlobalShipAdapter implements ShippingProvider
{
    private const float GRAMS_PER_OUNCE = 28.3495;

    public function __construct(
        private GlobalShipClient $client,
        private int $usdToCzkRateInHundredths,
    ) {
    }

    public function quote(string $countryCode, int $weightInGrams, int $orderValueInCents): ShippingQuote
    {
        $response = $this->client->getRate(
            ounces: (int) ceil($weightInGrams / self::GRAMS_PER_OUNCE),
            destination: $countryCode,
            express: $orderValueInCents > 500000,   // drahé zásilky posíláme expres
        );

        return new ShippingQuote(
            carrier: $response->serviceName,
            priceInCents: (int) round($response->amountUsdCents * $this->usdToCzkRateInHundredths / 100),
            deliveryDays: (int) ceil($response->transitHours / 24),
        );
    }
}
