<?php

declare(strict_types=1);

/**
 * Košík, který si drží mezivýsledek.
 *
 * Každá metoda počítá z toho, co je v poli PRÁVĚ TEĎ. Kód vypadá
 * nevinně a každá metoda je sama o sobě správně — jenže výsledek
 * závisí na tom, v jakém pořadí se zavolají.
 */
final class MutableCart
{
    private int $totalInCents = 0;

    private int $discountPercent = 0;

    public function addItem(int $priceInCents): void
    {
        $this->totalInCents += $priceInCents;
    }

    public function applyCoupon(int $percent): void
    {
        $this->discountPercent = $percent;
        $this->totalInCents = (int) round($this->totalInCents * (100 - $percent) / 100);
    }

    public function addShipping(int $priceInCents): void
    {
        $this->totalInCents += $priceInCents;
    }

    public function total(): int
    {
        return $this->totalInCents;
    }
}
