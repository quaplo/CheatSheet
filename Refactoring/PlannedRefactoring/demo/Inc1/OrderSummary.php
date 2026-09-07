<?php

declare(strict_types=1);

namespace Inc1;

/** Souhrn objednávky pro administraci. */
final class OrderSummary
{
    public function forAdmin(int $total, int $quantity, int $customerTier): string
    {
        $discount = 0;

        if ($quantity >= 100) {
            $discount += 10;
        }

        if ($customerTier <= 2) {
            $discount += 5;
        }

        if ($discount > 12) {
            $discount = 12;
        }

        $final = (int) round($total * (100 - $discount) / 100);

        return $final . ' Kč / sleva ' . $discount . ' %';
    }
}
