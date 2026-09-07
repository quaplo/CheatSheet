<?php

declare(strict_types=1);

namespace Step0;

/**
 * VÝCHOZÍ STAV.
 *
 * Zadání: přidat nový způsob dopravy — výdejní místa Balíkovny.
 * Funguje to, ale všechno je v jedné metodě a v jednom `if` řetězu.
 */
final class Shipping
{
    public function priceInCents(string $carrier, int $weightInGrams, int $orderValueInCents): int
    {
        if ($carrier === 'ppl') {
            if ($orderValueInCents >= 250000) {
                return 0;
            }

            return 9900;
        }

        if ($carrier === 'dhl') {
            $kilos = (int) ceil($weightInGrams / 1000);

            return 24900 + max(0, $kilos - 1) * 2000;
        }

        if ($carrier === 'pickup') {
            return 0;
        }

        throw new \InvalidArgumentException('Neznámý dopravce: ' . $carrier);
    }
}
