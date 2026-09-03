<?php

declare(strict_types=1);

namespace Domain;

/** Value object. Neplatná instance nevznikne. */
final readonly class EmailAddress
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $normalized = mb_strtolower(trim($value));

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException(sprintf('„%s“ není platná e-mailová adresa.', $value));
        }

        return new self($normalized);
    }
}
