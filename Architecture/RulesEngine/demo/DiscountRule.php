<?php

declare(strict_types=1);

/**
 * Jedno pravidlo.
 *
 * Rozpadá se na tři věci, a to rozdělení je celý pattern:
 *
 *   name()     — pravidlo má jméno, takže se dá vypsat a mluvit o něm
 *   appliesTo() — podmínka (v podstatě Specification)
 *   discountFor() — důsledek
 *
 * Podmínka a důsledek jsou schválně oddělené. Díky tomu jde zjistit,
 * která pravidla *sedla*, i když se nakonec uplatnilo jen jedno.
 */
interface DiscountRule
{
    public function name(): string;

    /** Vyšší číslo = dřív na řadě. */
    public function priority(): int;

    public function appliesTo(DiscountContext $context): bool;

    /** Sleva v haléřích. */
    public function discountFor(DiscountContext $context): int;
}
