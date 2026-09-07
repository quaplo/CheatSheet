<?php

declare(strict_types=1);

namespace TooBig;

/**
 * ODPADEK, KTERÝ UŽ ODPADEK NENÍ.
 *
 * Při úklidu je vidět další věc: třída počítá cenu A formátuje ji
 * pro dvě různá místa. To je porušení SRP a chce to vlastní třídu.
 *
 * Jenže tohle už není „cestou uklidím". Je to změna, která sáhne
 * na volající kód. Fowler na to má odpověď: odlož to a poznamenej.
 */
final class Shipping
{
    private const int FREE_FROM = 100000;
    private const int PRICE = 9900;
    private const int HEAVY_SURCHARGE = 4900;
    private const int HEAVY_FROM_GRAMS = 10000;
    private const int SLOVAKIA_SURCHARGE = 5000;

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
}
