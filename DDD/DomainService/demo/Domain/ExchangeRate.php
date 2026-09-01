<?php

declare(strict_types=1);

namespace Domain;

/** Value object. Kurz zná svoje dvě měny a poměr — nic víc. */
final readonly class ExchangeRate
{
    private function __construct(
        public Currency $from,
        public Currency $to,
        public int $rateInTenThousandths,
    ) {
    }

    public static function of(Currency $from, Currency $to, float $rate): self
    {
        return new self($from, $to, (int) round($rate * 10000));
    }

    public function describe(): string
    {
        return sprintf('1 %s = %.4f %s', $this->from->value, $this->rateInTenThousandths / 10000, $this->to->value);
    }
}
