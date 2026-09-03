<?php

declare(strict_types=1);

/**
 * Identita objednávky. Value object — viz pattern Value Object.
 *
 * Podstatné je, že identitu umí vyrobit aplikace, ne databáze. Díky tomu
 * existuje platný agregát dřív, než se vůbec něco uloží.
 */
final readonly class OrderId
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function generate(): self
    {
        return new self(strtoupper(bin2hex(random_bytes(4))));
    }

    public static function fromString(string $value): self
    {
        if ($value === '') {
            throw new InvalidArgumentException('Identita nesmí být prázdná.');
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
