<?php

declare(strict_types=1);

/**
 * Co dělat, když sedne víc pravidel najednou.
 *
 * Tohle NENÍ technický detail engine — je to byznysové rozhodnutí.
 * „Sčítají se slevy, nebo platí jen ta nejvyšší?“ je otázka na produkťáka,
 * ne na programátora. Pattern jenom nutí ji položit nahlas, místo aby
 * odpověď vznikla omylem podle pořadí `if`ů.
 */
enum ConflictResolution
{
    /** Uplatní se první pravidlo podle priority, ostatní se zahodí. */
    case FirstMatch;

    /** Uplatní se to, které dá největší slevu. */
    case BestForCustomer;

    /** Slevy se sčítají. */
    case Accumulate;

    public function label(): string
    {
        return match ($this) {
            self::FirstMatch => 'první podle priority',
            self::BestForCustomer => 'nejvýhodnější pro zákazníka',
            self::Accumulate => 'sčítat vše',
        };
    }
}
