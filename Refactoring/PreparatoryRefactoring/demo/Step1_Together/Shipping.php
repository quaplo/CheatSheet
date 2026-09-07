<?php

declare(strict_types=1);

namespace Step1_Together;

/**
 * CESTA A: přidat funkci rovnou do stávajícího kódu.
 *
 * Balíkovna přibyla jako další `if`. Funguje to, ale metoda je zase
 * o kus delší a pravidlo o dopravě zdarma se muselo zopakovat.
 *
 * Diff proti výchozímu stavu je malý — ale kód je horší než předtím.
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

        if ($carrier === 'balikovna') {
            if ($orderValueInCents >= 250000) {
                return 0;
            }

            return $weightInGrams > 10000 ? 12900 : 6900;
        }

        throw new \InvalidArgumentException('Neznámý dopravce: ' . $carrier);
    }
}
