<?php

declare(strict_types=1);

namespace Inc3;

/** Souhrn objednávky pro administraci. */
final class OrderSummary
{
    public function __construct(private readonly DiscountPolicy $policy)
    {
    }

    public function forAdmin(int $total, int $quantity, int $customerTier): string
    {
        $discount = $this->policy->percentFor($quantity, $customerTier);
        $final = $this->policy->applyTo($total, $quantity, $customerTier);

        return $final . ' Kč / sleva ' . $discount . ' %';
    }
}
