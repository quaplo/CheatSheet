<?php

declare(strict_types=1);

namespace Layered\Domain;

use Layered\Infrastructure\Db;

/**
 * Doménové pravidlo — a přímo pod ním čtení z databáze.
 *
 * Podle pravidla vrstev je to v pořádku: infrastruktura je NÍŽ,
 * takže se na ni smí sahat. A právě proto se doména pořád nedá
 * otestovat bez databáze.
 */
final class OrderTotals
{
    public function totalFor(string $orderId, int $customerTier): int
    {
        $total = 0;

        foreach (Db::lines($orderId) as $line) {
            $total += $line['price'] * $line['quantity'];
        }

        return $customerTier <= 2 ? (int) round($total * 0.9) : $total;
    }
}
