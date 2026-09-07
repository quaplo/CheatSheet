<?php

declare(strict_types=1);

namespace Before;

/**
 * PŘED: jeden konstruktor pro tři různé situace.
 *
 * Objednávka vzniká třemi způsoby — zákazník ji vytvoří v e-shopu,
 * přijde importem od partnera, nebo se načte z databáze. Všechny tři
 * jdou přes tentýž konstruktor, takže se musí rozlišit parametry.
 *
 * Výsledek je podpis, ze kterého na místě volání nikdo nepozná,
 * co se vlastně děje.
 */
final class Order
{
    /** @param list<string> $items */
    public function __construct(
        public readonly string $number,
        public readonly array $items,
        public readonly ?string $customerId,
        public readonly ?string $partnerCode,
        public readonly bool $isPaid,
        public readonly bool $skipStockCheck,
    ) {
    }
}
