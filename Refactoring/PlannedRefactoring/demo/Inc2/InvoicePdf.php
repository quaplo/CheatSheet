<?php

declare(strict_types=1);

namespace Inc2;

/** Řádek s celkovou částkou na faktuře. */
final class InvoicePdf
{
    public function __construct(private readonly DiscountPolicy $policy)
    {
    }

    public function totalLine(int $total, int $quantity, int $customerTier): string
    {
        $discount = $this->policy->percentFor($quantity, $customerTier);
        $final = $this->policy->applyTo($total, $quantity, $customerTier);

        return 'Celkem ' . $final . ' Kč (sleva ' . $discount . ' %)';
    }
}
