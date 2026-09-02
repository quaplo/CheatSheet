<?php

declare(strict_types=1);

namespace Domain;

/** Hodnota. V databázi z ní budou DVA sloupce — a doménu to nezajímá. */
final readonly class Money
{
    private function __construct(
        public int $amountInCents,
        public string $currency,
    ) {
    }

    public static function fromCents(int $amountInCents, string $currency = 'CZK'): self
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

    public function multipliedBy(int $factor): self
    {
        return new self($this->amountInCents * $factor, $this->currency);
    }

    public function format(): string
    {
        return number_format($this->amountInCents / 100, 2, ',', ' ') . ' ' . $this->currency;
    }
}
