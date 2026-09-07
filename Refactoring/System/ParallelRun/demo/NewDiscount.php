<?php

declare(strict_types=1);

/**
 * KANDIDÁT (candidate) — nová implementace, která se ověřuje.
 *
 * Napsaná podle toho, jak pravidla popsal byznys. Otázka je,
 * jestli to odpovídá tomu, co starý kód doopravdy dělá.
 *
 * Dvě odchylky jsou schválně a každá je jiného druhu:
 *   · jiné zaokrouhlení — haléřový šum, nikoho nezajímá
 *   · strop se počítá před kupónem, ne po něm — skutečná změna pravidla
 *
 * Parallel run najde obojí. Rozlišit je musí člověk.
 */
final class NewDiscount implements DiscountCalculator
{
    public function discountInCents(Order $order): int
    {
        $rate = match (true) {
            $order->totalInCents >= 500000 => 0.10,
            $order->totalInCents >= 200000 => 0.05,
            default => 0.0,
        };

        if ($order->customerTier === 'vip') {
            $rate += 0.03;
        }

        // Zaokrouhlení dolů místo matematického — drobnost s následky.
        $discount = (int) floor($order->totalInCents * $rate);

        // Strop se tu aplikuje DŘÍV než kupón, ve staré verzi až po něm.
        $discount = min($discount, (int) round($order->totalInCents * 0.25));

        if ($order->hasCoupon) {
            $discount += 10000;
        }

        return $discount;
    }
}
