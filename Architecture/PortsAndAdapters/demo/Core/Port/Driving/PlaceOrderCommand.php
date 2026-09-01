<?php

declare(strict_types=1);

namespace Core\Port\Driving;

/**
 * Vstup do use-case v pojmech jádra.
 *
 * Tenhle objekt je hranice: adaptér přeloží HTTP request, CLI argumenty
 * nebo řádek z fronty do tohohle tvaru. Jádro pak nemusí vědět, odkud
 * podnět přišel.
 */
final readonly class PlaceOrderCommand
{
    public function __construct(
        public string $customerEmail,
        public int $totalInCents,
    ) {
    }
}
