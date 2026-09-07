<?php

declare(strict_types=1);

namespace TooBig;

/**
 * Formátování ceny dopravy — vytažené ze Shipping.
 *
 * Sama o sobě je to správná změna. Špatné je jen to, kdy se dělá:
 * uprostřed opravy chyby, na kterou čeká zákaznická podpora.
 */
final class ShippingLabel
{
    private const string FREE_LABEL = 'zdarma';

    public function __construct(private readonly Shipping $shipping)
    {
    }

    public function label(int $orderValue, int $weightGrams, string $countryCode): string
    {
        $price = $this->shipping->priceFor($orderValue, $weightGrams, $countryCode);

        return $price === 0 ? self::FREE_LABEL : number_format($price / 100, 2, ',', ' ') . ' Kč';
    }

    public function receiptLine(int $orderValue, int $weightGrams, string $countryCode): string
    {
        $price = $this->shipping->priceFor($orderValue, $weightGrams, $countryCode);

        return 'Doprava;' . ($price === 0 ? self::FREE_LABEL : (string) $price);
    }
}
