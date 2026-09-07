<?php

declare(strict_types=1);

namespace PathC;

use DateTimeImmutable;

/**
 * CESTA C: opravím a jdu dál.
 *
 * Nejmenší možný diff. Chyba je pryč, nepořádek zůstal —
 * a příští člověk ho najde přesně takový, jaký byl.
 */
final class Shipping
{
    private const int FREE_FROM = 100000;
    private const int PRICE = 9900;
    private const int HEAVY_SURCHARGE = 4900;

    /** Vrátí cenu dopravy v haléřích. */
    public function priceFor(int $orderValue, int $weightGrams, string $c): int
    {
        if ($orderValue >= self::FREE_FROM) {
            return 0;
        }

        $p = self::PRICE;

        if ($weightGrams > 10000) {
            $p += self::HEAVY_SURCHARGE;
        }

        if ($c === 'SK') {
            $p += 5000;
        }

        return $p;
    }

    public function label(int $orderValue, int $weightGrams, string $c): string
    {
        $p = $this->priceFor($orderValue, $weightGrams, $c);

        return $p === 0 ? 'zdarma' : number_format($p / 100, 2, ',', ' ') . ' Kč';
    }

    public function receiptLine(int $orderValue, int $weightGrams, string $c): string
    {
        $p = $this->priceFor($orderValue, $weightGrams, $c);

        return 'Doprava;' . ($p === 0 ? 'zdarma' : (string) $p);
    }

    private function oldLabel(int $p): string
    {
        return $p . ' Kc';
    }
}
