<?php

declare(strict_types=1);

namespace Domain;

/** Identita. Value object — neměnná, porovnává se hodnotou. */
final readonly class CustomerId
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function generate(): self
    {
        return new self('CUST-' . strtoupper(bin2hex(random_bytes(3))));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
