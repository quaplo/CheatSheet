<?php

declare(strict_types=1);

final class Cart
{
    private const int VAT_PERCENT = 21;

    /** @var list<int> ceny položek v haléřích */
    private array $items = [];

    public function add(int $price): void
    {
        $this->items[] = $price;
    }

    public function itemCount(): int
    {
        return count($this->items);
    }

    public function total(): int
    {
        return (int) round(array_sum($this->items) * (100 + self::VAT_PERCENT) / 100);
    }
}
