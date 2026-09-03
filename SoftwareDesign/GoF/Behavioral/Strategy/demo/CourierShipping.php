<?php

declare(strict_types=1);

/**
 * Kurýr na adresu — základní cena plus příplatek za každý započatý kilogram
 * nad 5 kg. Do zahraničí je základní cena dvojnásobná.
 */
final readonly class CourierShipping implements ShippingCost
{
    private const int BASE_PRICE = 9900;
    private const int WEIGHT_LIMIT_GRAMS = 5000;
    private const int SURCHARGE_PER_KG = 1500;

    public function code(): string
    {
        return 'courier';
    }

    public function calculate(Order $order): int
    {
        $price = $order->countryCode === 'CZ'
            ? self::BASE_PRICE
            : self::BASE_PRICE * 2;

        $overweight = $order->weightInGrams - self::WEIGHT_LIMIT_GRAMS;

        if ($overweight > 0) {
            $price += (int) ceil($overweight / 1000) * self::SURCHARGE_PER_KG;
        }

        return $price;
    }
}
