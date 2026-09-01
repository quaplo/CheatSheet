<?php

declare(strict_types=1);

namespace Sales;

use Shared\CustomerId;

/**
 * Zákazník očima OBCHODU.
 *
 * Tady „zákazník“ znamená příležitost: někdo, s kým se možná uzavře
 * obchod. Zajímá nás hodnota, pravděpodobnost a kdo to má na starosti.
 *
 * O DPH, splatnosti ani o otevřených ticketech tenhle model neví nic —
 * a to není nedostatek. Obchodník je k ničemu nepotřebuje.
 */
final readonly class Customer
{
    public function __construct(
        public CustomerId $id,
        public string $companyName,
        public string $contactPerson,
        public int $dealValueInCents,
        public int $probabilityPercent,
        public string $accountManager,
    ) {
    }

    /** Vážená hodnota příležitosti — pojem, který existuje jen tady. */
    public function weightedValue(): int
    {
        return intdiv($this->dealValueInCents * $this->probabilityPercent, 100);
    }

    public function isWorthPursuing(): bool
    {
        return $this->weightedValue() >= 50000;
    }
}
