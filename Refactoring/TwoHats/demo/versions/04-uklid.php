<?php

declare(strict_types=1);

final class Cart
{
    private const int VAT_PERCENT = 21;
    private const int PERCENT_BASE = 100;

    /** @var list<int> ceny položek v haléřích */
    private array $pricesInCents = [];

    public function add(int $priceInCents): void
    {
        $this->pricesInCents[] = $priceInCents;
    }

    public function itemCount(): int
    {
        return count($this->pricesInCents);
    }

    /** Součet položek bez DPH, v haléřích. */
    public function totalWithoutVat(): int
    {
        return array_sum($this->pricesInCents);
    }

    /** Celková částka včetně DPH, v haléřích. */
    public function total(): int
    {
        return $this->withVat($this->totalWithoutVat());
    }

    private function withVat(int $amountInCents): int
    {
        return (int) round($amountInCents * (self::PERCENT_BASE + self::VAT_PERCENT) / self::PERCENT_BASE);
    }
}
