<?php

declare(strict_types=1);

namespace Adapter;

use Domain\ShippingProvider;
use Domain\ShippingQuote;
use Vendor\BalikovnaApi;

/**
 * ADAPTÉR pro prvního dopravce.
 *
 * Nedělá nic než překlad. Nemá vlastní logiku, nerozhoduje,
 * nic si nepamatuje — jen srovná dva neslučitelné tvary:
 *
 *   gramy    → kilogramy
 *   koruny   → haléře
 *   „2-3 dny“ → 3 (bereme horní odhad — a to je rozhodnutí,
 *                  které patří sem, ne do domény)
 *   pole      → ShippingQuote
 *
 * Tenhle typ adaptéru se v GoF nazývá **objektový**: cizí objekt
 * drží uvnitř. Druhá varianta z knihy — dědit z něj — v PHP
 * u `final` tříd ani nejde, a i kdyby, nebyla by lepší.
 */
final readonly class BalikovnaAdapter implements ShippingProvider
{
    public function __construct(
        private BalikovnaApi $api,
    ) {
    }

    public function quote(string $countryCode, int $weightInGrams, int $orderValueInCents): ShippingQuote
    {
        $response = $this->api->spocitejCenu($countryCode, $weightInGrams / 1000);

        return new ShippingQuote(
            carrier: $response['sluzba'],
            priceInCents: (int) round($response['cena'] * 100),
            deliveryDays: $this->parseDays($response['lhuta']),
        );
    }

    /** „2-3 dny“ → 3. Bereme horní odhad, ať zákazníka nezklameme. */
    private function parseDays(string $lhuta): int
    {
        preg_match_all('/\d+/', $lhuta, $matches);

        return $matches[0] === [] ? 0 : max(array_map('intval', $matches[0]));
    }
}
