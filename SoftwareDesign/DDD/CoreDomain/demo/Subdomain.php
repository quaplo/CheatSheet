<?php

declare(strict_types=1);

/**
 * Podoblast systému a odpovědi na otázky, podle kterých se klasifikuje.
 *
 * Odpovědi nejsou technické — jsou to otázky pro člověka, který ví,
 * čím firma vydělává. Právě proto se klasifikace nedá odvodit z kódu.
 */
final readonly class Subdomain
{
    public function __construct(
        public string $name,
        /** Odlišuje nás to od konkurence? */
        public bool $differentiates,
        /** Existuje na to hotové řešení, které jde koupit nebo stáhnout? */
        public bool $availableOffTheShelf,
        /** Dělala by to jiná firma v jiném oboru úplně stejně? */
        public bool $sameEverywhere,
        /** Kolik člověkodnů se do toho letos investovalo. */
        public int $effortInDays,
    ) {
    }
}
