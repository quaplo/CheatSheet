<?php

declare(strict_types=1);

/**
 * KONTROLA (control) — kód, který běží roky a je zdrojem pravdy.
 *
 * Nikdo přesně neví, proč jsou ta čísla taková, jaká jsou.
 * A právě proto se nedá jen tak nahradit.
 */
final class LegacyDiscount implements DiscountCalculator
{
    public function discountInCents(Order $order): int
    {
        $discount = 0;

        if ($order->totalInCents >= 500000) {
            $discount += (int) round($order->totalInCents * 0.10);
        } elseif ($order->totalInCents >= 200000) {
            $discount += (int) round($order->totalInCents * 0.05);
        }

        if ($order->customerTier === 'vip') {
            $discount += (int) round($order->totalInCents * 0.03);
        }

        if ($order->hasCoupon) {
            $discount += 10000;
        }

        // Strop, na který si nikdo nepamatuje, kdy a proč vznikl.
        return min($discount, (int) round($order->totalInCents * 0.25));
    }
}
