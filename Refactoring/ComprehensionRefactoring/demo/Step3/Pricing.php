<?php

declare(strict_types=1);

namespace Step3;

/**
 * KROK 3: rozdělit na věty.
 *
 * Každá metoda odpovídá na jednu otázku a její jméno je ta odpověď.
 * Hlavní metoda se dá přečíst nahlas a dává smysl.
 *
 * Chování je od kroku 0 nezměněné. Změnilo se jen to, kolik toho
 * o něm kód říká sám.
 */
final class Pricing
{
    private const int WHOLESALE_QUANTITY = 100;
    private const int PREMIUM_TIER = 2;
    private const int GIFT_WRAP_PRICE = 2500;
    private const int SHIPPING_PRICE = 9900;
    private const int FREE_SHIPPING_FROM = 100000;

    /** @param list<array{0: int, 1: int, 2: int}> $items */
    public function calc(array $items, int $customerTier, bool $addShipping): int
    {
        $total = 0;

        foreach ($items as $item) {
            $total += $this->lineTotal($item, $customerTier);
        }

        return $total + $this->shippingFor($total, $addShipping);
    }

    /** @param array{0: int, 1: int, 2: int} $item */
    private function lineTotal(array $item, int $customerTier): int
    {
        [$unitPrice, $quantity, $isGiftWrapped] = $item;

        $subtotal = $this->discounted(
            $unitPrice * $quantity,
            $this->isWholesale($quantity),
            $this->isPremiumCustomer($customerTier),
        );

        return $isGiftWrapped === 1 ? $subtotal + self::GIFT_WRAP_PRICE : $subtotal;
    }

    private function discounted(int $amount, bool $isWholesale, bool $isPremium): int
    {
        $rate = match (true) {
            $isWholesale && $isPremium => 0.8,
            $isWholesale => 0.9,
            $isPremium => 0.95,
            default => 1.0,
        };

        return $rate === 1.0 ? $amount : (int) ($amount * $rate);
    }

    private function isWholesale(int $quantity): bool
    {
        return $quantity >= self::WHOLESALE_QUANTITY;
    }

    private function isPremiumCustomer(int $customerTier): bool
    {
        return $customerTier <= self::PREMIUM_TIER;
    }

    private function shippingFor(int $total, bool $addShipping): int
    {
        if (!$addShipping) {
            return 0;
        }

        return $total < self::FREE_SHIPPING_FROM ? self::SHIPPING_PRICE : 0;
    }
}
