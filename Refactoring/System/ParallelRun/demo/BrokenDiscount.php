<?php

declare(strict_types=1);

/**
 * Kandidát, který občas spadne.
 *
 * Je tu proto, aby bylo vidět to nejdůležitější: výjimka v kandidátovi
 * nesmí shodit systém. Zákazník dostane odpověď z kontroly a o tom,
 * že se něco pokazilo, se dozví jen tým.
 */
final class BrokenDiscount implements DiscountCalculator
{
    public function discountInCents(Order $order): int
    {
        if ($order->itemCount === 0) {
            throw new DivisionByZeroError('Dělení nulou při průměru na položku.');
        }

        return (int) round($order->totalInCents * 0.10);
    }
}
