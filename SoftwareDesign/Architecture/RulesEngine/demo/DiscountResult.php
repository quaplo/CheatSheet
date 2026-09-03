<?php

declare(strict_types=1);

/**
 * Výsledek vyhodnocení — a hlavně jeho zdůvodnění.
 *
 * Auditní stopa je to, kvůli čemu se pattern vyplatí víc než kvůli
 * čemukoli jinému. Na dotaz „proč tenhle zákazník dostal tuhle cenu?“
 * existuje odpověď, a nemusí se kvůli ní ladit kód v produkci.
 */
final readonly class DiscountResult
{
    /**
     * @param list<AppliedRule> $evaluated pravidla, která sedla
     */
    public function __construct(
        public int $totalDiscountInCents,
        public ConflictResolution $strategy,
        public array $evaluated,
    ) {
    }

    /** @return list<AppliedRule> */
    public function used(): array
    {
        return array_values(array_filter($this->evaluated, static fn (AppliedRule $r): bool => $r->wasUsed));
    }

    /** @return list<AppliedRule> pravidla, která sedla, ale neuplatnila se */
    public function shadowed(): array
    {
        return array_values(array_filter($this->evaluated, static fn (AppliedRule $r): bool => $r->wasUsed === false));
    }
}
