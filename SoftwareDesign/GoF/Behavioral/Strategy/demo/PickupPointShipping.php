<?php

declare(strict_types=1);

/**
 * Výdejní místo — pevná cena, nad 1 500 Kč zdarma.
 */
final readonly class PickupPointShipping implements ShippingCost
{
    private const int BASE_PRICE = 6900;
    private const int FREE_FROM = 150000;

    public function code(): string
    {
        return 'pickup_point';
    }

    public function calculate(Order $order): int
    {
        if ($order->totalInCents >= self::FREE_FROM) {
            return 0;
        }

        return self::BASE_PRICE;
    }
}
