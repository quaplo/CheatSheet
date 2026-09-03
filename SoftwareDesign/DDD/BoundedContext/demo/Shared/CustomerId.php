<?php

declare(strict_types=1);

namespace Shared;

/**
 * Jediné, na čem se všechny kontexty shodnou: identita.
 *
 * Pozor — i tenhle drobek je **sdílené jádro** (shared kernel). Vypadá
 * nevinně, ale je to závazek: kdo ho změní, změní ho všem. Proto má být
 * co nejmenší a má být vědomý, ne vzniklý omylem. Viz Context Map.
 */
final readonly class CustomerId
{
    private function __construct(
        public string $value,
    ) {
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
