<?php

declare(strict_types=1);

namespace Inc1;

/** Řádek s celkovou částkou na faktuře. */
final class InvoicePdf
{
    public function totalLine(int $total, int $quantity, int $customerTier): string
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

        return 'Celkem ' . $final . ' Kč (sleva ' . $discount . ' %)';
    }
}
