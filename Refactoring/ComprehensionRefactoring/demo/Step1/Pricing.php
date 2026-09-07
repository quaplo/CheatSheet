<?php

declare(strict_types=1);

namespace Step1;

/**
 * KROK 1: pojmenovat, co už víš.
 *
 * Nic se nepřesouvalo, nic se nerozdělovalo. Jen dostaly jména
 * proměnné a parametry — podle toho, co se s nimi dělá.
 *
 * Struktura je pořád stejná a stejně nepřehledná. Ale už je vidět,
 * o čem to je.
 */
final class Pricing
{
    /** @param list<array{0: int, 1: int, 2: int}> $items */
    public function calc(array $items, int $customerTier, bool $addShipping): int
    {
        $total = 0;

        foreach ($items as $item) {
            [$unitPrice, $quantity, $isGiftWrapped] = $item;
            $subtotal = $unitPrice * $quantity;

            if ($quantity >= 100 && $customerTier <= 2) {
                $subtotal = (int) ($subtotal * 0.8);
            } elseif ($quantity >= 100) {
                $subtotal = (int) ($subtotal * 0.9);
            } elseif ($customerTier <= 2) {
                $subtotal = (int) ($subtotal * 0.95);
            }

            if ($isGiftWrapped === 1) {
                $subtotal += 2500;
            }

            $total += $subtotal;
        }

        if ($addShipping && $total < 100000) {
            $total += 9900;
        }

        return $total;
    }
}
