<?php

declare(strict_types=1);

/**
 * Týž košík, ale bez mezivýsledku.
 *
 * Metody jen sbírají fakta. Celková částka se počítá až na konci,
 * a proto na pořadí volání nezáleží — není co „rozbít v půlce".
 */
final readonly class ImmutableCart
{
    /** @param list<int> $itemPricesInCents */
    private function __construct(
        private array $itemPricesInCents,
        private int $discountPercent,
        private int $shippingInCents,
    ) {
    }

    public static function empty(): self
    {
        return new self([], 0, 0);
    }

    public function withItem(int $priceInCents): self
    {
        return new self([...$this->itemPricesInCents, $priceInCents], $this->discountPercent, $this->shippingInCents);
    }

    public function withCoupon(int $percent): self
    {
        return new self($this->itemPricesInCents, $percent, $this->shippingInCents);
    }

    public function withShipping(int $priceInCents): self
    {
        return new self($this->itemPricesInCents, $this->discountPercent, $priceInCents);
    }

    public function total(): int
    {
        $items = array_sum($this->itemPricesInCents);
        $discounted = (int) round($items * (100 - $this->discountPercent) / 100);

        return $discounted + $this->shippingInCents;
    }
}
