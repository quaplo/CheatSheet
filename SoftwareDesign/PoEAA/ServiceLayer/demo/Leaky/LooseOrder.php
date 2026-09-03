<?php

declare(strict_types=1);

namespace Leaky;

/**
 * Doména BEZ pravidla o limitu — protože se přesunulo do use-case.
 *
 * Vypadá to nevinně: „ten limit potřebuje data z jiného agregátu,
 * tak ať to řeší aplikační vrstva.“ Za chvíli uvidíš, co to udělá.
 */
final class LooseOrder
{
    private function __construct(
        public readonly string $id,
        public readonly string $customerId,
        public readonly int $totalInCents,
    ) {
    }

    public static function place(string $id, string $customerId, int $totalInCents): self
    {
        if ($totalInCents <= 0) {
            throw new \DomainException('Objednávka musí mít kladnou hodnotu.');
        }

        return new self($id, $customerId, $totalInCents);
    }
}
