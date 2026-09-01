<?php

declare(strict_types=1);

/**
 * Fakta, nad kterými se pravidla vyhodnocují.
 *
 * V terminologii pravidlových systémů je tohle „working memory“ — všechno,
 * co engine o situaci ví. Držet to v jednom neměnném objektu je důležité:
 * pravidlo nesmí mít možnost si fakta cestou doplnit nebo změnit.
 */
final readonly class DiscountContext
{
    public function __construct(
        public string $orderNumber,
        public int $orderTotalInCents,
        public int $itemCount,
        public bool $isVipCustomer,
        public bool $isFirstOrder,
        public ?string $promoCode = null,
    ) {
    }
}
