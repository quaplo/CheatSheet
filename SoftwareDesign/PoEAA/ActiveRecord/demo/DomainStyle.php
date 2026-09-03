<?php

declare(strict_types=1);

namespace DomainStyle;

/**
 * Totéž pravidlo v Data Mapper stylu — jen pro srovnání rozsahu.
 *
 * Doména nezná databázi, takže pravidlo jde otestovat bez schématu
 * i bez spojení. Cena je v tom, že k tomu patří ještě repository
 * a mapper, které tady nejsou.
 */
final class Order
{
    public function __construct(
        private readonly string $number,
        private readonly string $customerId,
        private readonly int $totalInCents,
        private string $status,
    ) {
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['nová', 'potvrzená'], true);
    }

    public function cancel(): void
    {
        if (!$this->canBeCancelled()) {
            throw new \DomainException(sprintf('Objednávku ve stavu „%s“ nelze zrušit.', $this->status));
        }

        $this->status = 'zrušená';
    }

    public function status(): string
    {
        return $this->status;
    }
}
