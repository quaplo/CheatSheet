<?php

declare(strict_types=1);

namespace Application\Query;

/**
 * Dotazovací handler.
 *
 * Všimni si, CO TU NENÍ oproti příkazovému handleru:
 *
 *   · žádná transakce   — nic se nemění
 *   · žádné události    — nic se nestalo
 *   · žádný agregát     — pravidla nemá co chránit
 *   · žádné repository  — čte se rovnou to, co obrazovka potřebuje
 *
 * Právě proto bývá dotazovací handler tenký až podezřele. Kdy se
 * vyplatí a kdy je to jen prázdná vrstva, řeší README.
 */
final readonly class OrderSummaryHandler
{
    public function __construct(
        private OrderReadSource $source,
    ) {
    }

    /** @return list<OrderSummary> */
    public function handle(OrderSummaryQuery $query): array
    {
        return $this->source->summariesFor($query->customerId, $query->limit);
    }
}
