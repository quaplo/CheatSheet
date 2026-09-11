<?php

declare(strict_types=1);

namespace Naive;

/**
 * Všechno v jedné třídě: čtení z databáze, pravidlo o slevě
 * i formátování výstupu.
 *
 * Přesně to, co Evans popisuje: „UI, database, and other support
 * code often gets written directly into the business objects."
 */
final class OrderReport
{
    public function render(string $orderId, int $customerTier): string
    {
        $total = 0;

        foreach (Db::lines($orderId) as $line) {
            $total += $line['price'] * $line['quantity'];
        }

        if ($customerTier <= 2) {
            $total = (int) round($total * 0.9);
        }

        return '<strong>' . number_format($total / 100, 2, ',', ' ') . ' Kč</strong>';
    }
}
