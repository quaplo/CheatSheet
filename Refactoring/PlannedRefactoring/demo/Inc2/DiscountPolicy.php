<?php

declare(strict_types=1);

namespace Inc2;

/**
 * Jedno místo, které zná pravidlo pro slevu.
 *
 * Cíl refaktoringové story „sjednotit výpočet slevy".
 */
final class DiscountPolicy
{
    private const int WHOLESALE_FROM = 100;
    private const int PREMIUM_TIER = 2;
    private const int WHOLESALE_DISCOUNT = 10;
    private const int PREMIUM_DISCOUNT = 5;
    private const int MAX_DISCOUNT = 12;

    public function percentFor(int $quantity, int $customerTier): int
    {
        return min($this->percentWithoutCap($quantity, $customerTier), self::MAX_DISCOUNT);
    }

    public function percentWithoutCap(int $quantity, int $customerTier): int
    {
        $discount = 0;

        if ($quantity >= self::WHOLESALE_FROM) {
            $discount += self::WHOLESALE_DISCOUNT;
        }

        if ($customerTier <= self::PREMIUM_TIER) {
            $discount += self::PREMIUM_DISCOUNT;
        }

        return $discount;
    }

    public function applyTo(int $total, int $quantity, int $customerTier): int
    {
        return (int) round($total * (100 - $this->percentFor($quantity, $customerTier)) / 100);
    }
}
