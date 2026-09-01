<?php

declare(strict_types=1);

namespace Domain;

/**
 * Identita CIZÍHO agregátu.
 *
 * Objednávka drží jen tohle — ne celý objekt Customer. Kdyby držela
 * objekt, načetla by se s ním půlka databáze a hlavně by vznikla otázka,
 * kdo ho smí měnit. Odkaz identitou tuhle otázku ruší.
 */
final readonly class CustomerId
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
