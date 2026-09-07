<?php

declare(strict_types=1);

namespace Abandoned;

/** Export objednávek do CSV. */
final class OrderExport
{
    public function __construct(private readonly DiscountPolicy $policy)
    {
    }

    public function row(int $total, int $quantity, int $customerTier): string
    {
        // Migrace zůstala v půlce: procenta už umí policy, ale strop
        // se sem nestihl přesunout a zůstal tady podruhé.
        $discount = $this->policy->percentWithoutCap($quantity, $customerTier);

        if ($discount > 12) {
            $discount = 12;
        }

        $final = (int) round($total * (100 - $discount) / 100);

        return 'objednavka;' . $final . ';' . $discount;
    }
}
