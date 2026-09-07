<?php

declare(strict_types=1);

namespace Before;

/**
 * Kolik vrátit za zrušenou objednávku.
 *
 * Metoda je správně a projde všechny testy. Jen se nedá přečíst —
 * abys věděl, co dělá, musíš ji celou rozluštit.
 */
final class RefundPolicy
{
    /** @param array<string, mixed> $order */
    public function amountFor(array $order, int $now): int
    {
        if ($order['shippedAt'] === null
            && $now - $order['paidAt'] < 14 * 24 * 3600
            && $order['paymentMethod'] !== 'dobirka') {
            $amount = $order['totalInCents'];

            if ($order['giftWrapped']) {
                $amount -= 2500;
            }

            return $amount;
        }

        if ($order['shippedAt'] !== null
            && $now - $order['shippedAt'] < 14 * 24 * 3600
            && $order['returnedAt'] !== null) {
            $amount = $order['totalInCents'] - $order['shippingInCents'];

            if ($order['giftWrapped']) {
                $amount -= 2500;
            }

            if ($amount < 0) {
                $amount = 0;
            }

            return $amount;
        }

        return 0;
    }
}
