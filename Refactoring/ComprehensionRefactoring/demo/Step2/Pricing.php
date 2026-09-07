<?php

declare(strict_types=1);

namespace Step2;

/**
 * KROK 2: pojmenovat podmínky.
 *
 * Tady se to začíná lámat. Podmínky dostaly jména — a tím se ukázalo,
 * že tenhle kód zná pravidla, o kterých nikde nic není:
 *
 *   · sto kusů a víc je velkoobchodní množství
 *   · zákaznická úroveň 1 a 2 je „prémiový zákazník"
 *   · obojí zároveň má vlastní, vyšší slevu
 *
 * Nikdo z nás ta pravidla nevymyslel. Byla tam celou dobu, jen
 * nebyla vidět.
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
            [$unitPrice, $quantity, $isGiftWrapped] = $item;
            $subtotal = $unitPrice * $quantity;

            $isWholesale = $quantity >= self::WHOLESALE_QUANTITY;
            $isPremiumCustomer = $customerTier <= self::PREMIUM_TIER;

            if ($isWholesale && $isPremiumCustomer) {
                $subtotal = (int) ($subtotal * 0.8);
            } elseif ($isWholesale) {
                $subtotal = (int) ($subtotal * 0.9);
            } elseif ($isPremiumCustomer) {
                $subtotal = (int) ($subtotal * 0.95);
            }

            if ($isGiftWrapped === 1) {
                $subtotal += self::GIFT_WRAP_PRICE;
            }

            $total += $subtotal;
        }

        if ($addShipping && $total < self::FREE_SHIPPING_FROM) {
            $total += self::SHIPPING_PRICE;
        }

        return $total;
    }
}
