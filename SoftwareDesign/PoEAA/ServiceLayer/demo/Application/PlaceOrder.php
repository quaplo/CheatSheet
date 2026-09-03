<?php

declare(strict_types=1);

namespace Application;

/**
 * Příkaz = vstupní kontrakt use-case.
 *
 * Popisuje ZÁMĚR, ne data k zobrazení. Adaptér (HTTP controller, CLI,
 * konzument fronty) přeloží svůj vstup do tohohle tvaru a use-case
 * pak nemusí vědět, odkud podnět přišel.
 */
final readonly class PlaceOrder
{
    public function __construct(
        public string $customerId,
        public int $totalInCents,
    ) {
    }
}
