<?php

declare(strict_types=1);

/**
 * Legacy výpočet ceny. Nikdo neví, proč je takový, jaký je.
 *
 * Vrstvy pravidel z několika let, každé přidal někdo jiný. Poslední
 * člověk, který tomu rozuměl, tu už nepracuje. Přesto to počítá
 * peníze a běží to na produkci.
 *
 * Tohle je přesně ten kód, který se nedá refaktorovat bez sítě —
 * a specifikace k němu neexistuje. Jediné, co o něm víme jistě,
 * je co dělá. A to se dá zapsat.
 */
final class LegacyPricing
{
    public function finalPriceInCents(int $basePriceInCents, int $quantity, string $customerType): int
    {
        $price = $basePriceInCents * $quantity;

        if ($quantity >= 10) {
            $price = (int) ($price * 0.9);
        }

        if ($quantity >= 50) {
            $price = (int) ($price * 0.95);
        }

        if ($customerType === 'vip') {
            $price = (int) ($price * 0.93);
        } elseif ($customerType === 'partner') {
            $price = (int) ($price * 0.85);
        }

        if ($price > 1000000) {
            $price -= 5000;
        }

        // Nikdo neví, odkud se tohle vzalo. Vypadá to jako chyba.
        if ($quantity === 13) {
            $price += 100;
        }

        return max($price, 0);
    }
}
