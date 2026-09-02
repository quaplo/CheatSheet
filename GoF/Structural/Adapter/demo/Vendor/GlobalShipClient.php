<?php

declare(strict_types=1);

namespace Vendor;

/**
 * CIZÍ knihovna dopravce č. 2. Úplně jiná než ta první.
 *
 *   · jiná metoda, jiné parametry, jiné pořadí
 *   · hmotnost v librách
 *   · cena v centech, ale v USD
 *   · lhůta jako počet hodin
 *   · vrací vlastní objekt
 */
final class GlobalShipClient
{
    public function getRate(int $ounces, string $destination, bool $express = false): RateResponse
    {
        $usdCents = 400 + $ounces * 3;

        if ($express) {
            $usdCents = (int) round($usdCents * 1.8);
        }

        return new RateResponse($usdCents, $express ? 24 : 96, 'GlobalShip');
    }
}

final readonly class RateResponse
{
    public function __construct(
        public int $amountUsdCents,
        public int $transitHours,
        public string $serviceName,
    ) {
    }
}
