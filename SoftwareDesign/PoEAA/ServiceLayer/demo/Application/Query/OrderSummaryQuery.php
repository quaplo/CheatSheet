<?php

declare(strict_types=1);

namespace Application\Query;

/**
 * Dotaz jako pojmenovaný vstup.
 *
 * Stejný princip jako u příkazu, jen s jiným záměrem: příkaz něco
 * MĚNÍ a dá se odmítnout, dotaz jen ČTE a nemá vedlejší efekty.
 * To je Meyerovo CQS povýšené na úroveň use-case.
 */
final readonly class OrderSummaryQuery
{
    public function __construct(
        public string $customerId,
        public int $limit = 10,
    ) {
    }
}
