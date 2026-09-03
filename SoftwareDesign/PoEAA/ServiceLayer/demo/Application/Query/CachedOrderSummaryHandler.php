<?php

declare(strict_types=1);

namespace Application\Query;

/**
 * Dekorátor — a zároveň hlavní argument pro to, aby dotazy měly
 * handler stejného tvaru jako příkazy.
 *
 * Když má všechno tvar `handle($vstup): $výstup`, jde kolem toho
 * obalit cokoli průřezového: cache, autorizaci, měření času, audit.
 * A u dotazů se cachovat dá, u příkazů ne — právě proto, že dotaz
 * nemá vedlejší efekty.
 */
final class CachedOrderSummaryHandler
{
    /** @var array<string, list<OrderSummary>> */
    private array $cache = [];

    public int $hits = 0;
    public int $misses = 0;

    public function __construct(
        private readonly OrderSummaryHandler $inner,
    ) {
    }

    /** @return list<OrderSummary> */
    public function handle(OrderSummaryQuery $query): array
    {
        $key = $query->customerId . '|' . $query->limit;

        if (isset($this->cache[$key])) {
            $this->hits++;

            return $this->cache[$key];
        }

        $this->misses++;

        return $this->cache[$key] = $this->inner->handle($query);
    }
}
