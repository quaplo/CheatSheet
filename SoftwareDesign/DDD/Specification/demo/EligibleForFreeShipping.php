<?php

declare(strict_types=1);

/**
 * Pravidlo, které má jméno i v byznysu, má mít jméno i v kódu.
 *
 * Uvnitř je to jen složenina jednodušších specifikací. Navenek je to
 * pojem, o kterém se dá mluvit s produkťákem — a když se podmínka pro
 * dopravu zdarma změní, mění se právě tady a nikde jinde.
 */
final class EligibleForFreeShipping extends OrderSpecification
{
    private readonly OrderSpecification $rule;

    public function __construct()
    {
        $this->rule = (new OrderIsPaid())
            ->and(new OrderTotalAtLeast(150000))
            ->and(new OrderShipsTo('CZ'));
    }

    public function isSatisfiedBy(Order $order): bool
    {
        return $this->rule->isSatisfiedBy($order);
    }

    public function describe(): string
    {
        return 'objednávka má nárok na dopravu zdarma';
    }

    public function reasonsForFailure(Order $order): array
    {
        return $this->rule->reasonsForFailure($order);
    }
}
