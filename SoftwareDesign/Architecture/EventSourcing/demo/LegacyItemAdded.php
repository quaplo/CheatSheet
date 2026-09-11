<?php

declare(strict_types=1);

/**
 * Událost, jak se ukládala před dvěma lety — bez množství.
 *
 * V proudu leží navždy. Nejde ji změnit migrací jako sloupec
 * v tabulce, protože je to záznam o tom, co se stalo.
 */
final readonly class LegacyItemAdded implements Event
{
    public function __construct(
        public string $sku,
        public int $priceInCents,
        private string $at,
    ) {
    }

    public function happenedAt(): string
    {
        return $this->at;
    }
}
