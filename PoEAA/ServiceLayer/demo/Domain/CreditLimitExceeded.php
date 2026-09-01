<?php

declare(strict_types=1);

namespace Domain;

/** Doménová chyba. Aplikační vrstva ji jen přeloží pro volajícího. */
final class CreditLimitExceeded extends \DomainException
{
    public static function by(int $totalInCents, int $limitInCents): self
    {
        return new self(sprintf(
            'Objednávka za %s Kč přesahuje úvěrový limit %s Kč.',
            number_format($totalInCents / 100, 0, ',', ' '),
            number_format($limitInCents / 100, 0, ',', ' '),
        ));
    }
}
