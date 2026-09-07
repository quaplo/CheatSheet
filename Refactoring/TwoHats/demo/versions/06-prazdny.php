<?php

declare(strict_types=1);

final class Cart
{
    private const int VAT_PERCENT = 21;
    private const int PERCENT_BASE = 100;
    private const int WHOLESALE_FROM = 100;
    private const int WHOLESALE_DISCOUNT_PERCENT = 10;

    /** @var list<int> ceny položek v haléřích */
    private array $pricesInCents = [];

    public function add(int $priceInCents): void
    {
        $this->pricesInCents[] = $priceInCents;
    }

    public function isEmpty(): bool
    {
        return $this->pricesInCents === [];
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
        return $this->withVat($this->discounted($this->totalWithoutVat()));
    }

    private function discounted(int $amountInCents): int
    {
        if ($this->itemCount() <= self::WHOLESALE_FROM) {
            return (int) round($amountInCents * (self::PERCENT_BASE - self::WHOLESALE_DISCOUNT_PERCENT) / self::PERCENT_BASE);
        }

        return $amountInCents;
    }

    private function withVat(int $amountInCents): int
    {
        return (int) round($amountInCents * (self::PERCENT_BASE + self::VAT_PERCENT) / self::PERCENT_BASE);
    }
}
