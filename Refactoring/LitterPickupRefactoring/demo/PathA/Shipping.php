<?php

declare(strict_types=1);

namespace PathA;

/**
 * CESTA A: oprava i úklid v jednom commitu.
 *
 * Chování se změnilo (opravená chyba) a struktura taky (úklid).
 * V diffu už nejde poznat, co z toho je co.
 */
final class Shipping
{
    private const int FREE_FROM = 100000;
    private const int PRICE = 9900;
    private const int HEAVY_SURCHARGE = 4900;
    private const int HEAVY_FROM_GRAMS = 10000;
    private const int SLOVAKIA_SURCHARGE = 5000;
    private const string FREE_LABEL = 'zdarma';

    /** Vrátí cenu dopravy v haléřích. */
    public function priceFor(int $orderValue, int $weightGrams, string $countryCode): int
    {
        if ($orderValue >= self::FREE_FROM) {
            return 0;
        }

        $price = self::PRICE;

        if ($weightGrams > self::HEAVY_FROM_GRAMS) {
            $price += self::HEAVY_SURCHARGE;
        }

        if ($countryCode === 'SK') {
            $price += self::SLOVAKIA_SURCHARGE;
        }

        return $price;
    }

    public function label(int $orderValue, int $weightGrams, string $countryCode): string
    {
        $price = $this->priceFor($orderValue, $weightGrams, $countryCode);

        return $price === 0 ? self::FREE_LABEL : number_format($price / 100, 2, ',', ' ') . ' Kč';
    }

    public function receiptLine(int $orderValue, int $weightGrams, string $countryCode): string
    {
        $price = $this->priceFor($orderValue, $weightGrams, $countryCode);

        return 'Doprava;' . ($price === 0 ? self::FREE_LABEL : (string) $price);
    }
}
