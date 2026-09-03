<?php

declare(strict_types=1);

namespace Persistence;

/**
 * Konflikt souběžné změny.
 *
 * Podstatné je, že to VŮBEC je výjimka. Bez optimistického zámku
 * se nic nestane — druhý zápis prostě přepíše první a nikdo se to
 * nedozví.
 */
final class ConcurrentModification extends \RuntimeException
{
    public static function of(string $number, int $expected, int $actual): self
    {
        return new self(sprintf(
            'Objednávku %s mezitím někdo změnil (očekávána verze %d, v databázi je %d).',
            $number,
            $expected,
            $actual,
        ));
    }
}
