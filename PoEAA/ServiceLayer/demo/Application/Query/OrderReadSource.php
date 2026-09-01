<?php

declare(strict_types=1);

namespace Application\Query;

/**
 * Čtecí zdroj. V produkci by tady bylo SQL vracející rovnou DTO —
 * viz CQRS. Doménu obchází záměrně: čtení nic nemění, takže nemá
 * co porušit.
 */
interface OrderReadSource
{
    /** @return list<OrderSummary> */
    public function summariesFor(string $customerId, int $limit): array;
}
