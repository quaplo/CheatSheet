<?php

declare(strict_types=1);

namespace Domain;

/** Naše identita dodavatele. O tom, že v ERP je to číslo, neví. */
final readonly class SupplierId
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Identita dodavatele nesmí být prázdná.');
        }

        return new self($value);
    }
}
