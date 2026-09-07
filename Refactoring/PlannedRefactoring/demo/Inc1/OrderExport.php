<?php

declare(strict_types=1);

namespace Inc1;

/** Export objednávek do CSV. */
final class OrderExport
{
    public function __construct(private readonly DiscountPolicy $policy)
    {
    }

    public function row(int $total, int $quantity, int $customerTier): string
    {
        $discount = $this->policy->percentFor($quantity, $customerTier);
        $final = $this->policy->applyTo($total, $quantity, $customerTier);

        return 'objednavka;' . $final . ';' . $discount;
    }
}
