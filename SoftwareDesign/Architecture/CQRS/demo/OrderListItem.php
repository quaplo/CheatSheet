<?php

declare(strict_types=1);

/**
 * ČTECÍ MODEL. Přesně jeden řádek tabulky v administraci.
 *
 * Není to entita a nikdy jí nebude. Nemá identitu v doménovém smyslu,
 * nemá chování a nehlídá žádná pravidla — je to tvar obrazovky.
 *
 * Proto je taky plochý a široký: součet a počet položek jsou tady
 * spočítané hodnoty, ne vypočítané z načtených objektů.
 */
final readonly class OrderListItem
{
    public function __construct(
        public string $id,
        public string $customerEmail,
        public string $status,
        public string $placedAt,
        public int $itemCount,
        public int $totalInCents,
    ) {
    }
}
