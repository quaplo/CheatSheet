<?php

declare(strict_types=1);

namespace Domain;

/** Value object. Umí to, co umí jedna částka sama o sobě. */
final readonly class Money
{
    private function __construct(
        public int $amountInCents,
        public Currency $currency,
    ) {
    }

    public static function fromCents(int $amountInCents, Currency $currency): self
    {
        return new self($amountInCents, $currency);
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Nelze sčítat různé měny.');
        }

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function format(): string
    {
        return number_format($this->amountInCents / 100, 2, ',', ' ') . ' ' . $this->currency->value;
    }
}
