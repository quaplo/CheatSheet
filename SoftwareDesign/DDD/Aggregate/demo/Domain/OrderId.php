<?php

declare(strict_types=1);

namespace Domain;

final readonly class OrderId
{
    private function __construct(public string $value)
    {
    }

    public static function generate(): self
    {
        return new self('OBJ-' . strtoupper(bin2hex(random_bytes(3))));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
