<?php

declare(strict_types=1);

final readonly class Money
{
    private function __construct(public int $amountInCents)
    {
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public function add(self $other): self
    {
        return new self($this->amountInCents + $other->amountInCents);
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->amountInCents > $other->amountInCents;
    }

    public function format(): string
    {
        return number_format($this->amountInCents / 100, 2, ',', ' ') . ' Kč';
    }
}
