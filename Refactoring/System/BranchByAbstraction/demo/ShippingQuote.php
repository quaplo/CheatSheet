<?php

declare(strict_types=1);

/**
 * Výsledek výpočtu dopravy — společný pro obě implementace.
 */
final readonly class ShippingQuote
{
    public function __construct(
        public string $carrier,
        public int $priceInCents,
        public int $deliveryDays,
    ) {
    }

    public function format(): string
    {
        return sprintf(
            '%s, %s, %d %s',
            $this->carrier,
            number_format($this->priceInCents / 100, 2, ',', ' ') . ' Kč',
            $this->deliveryDays,
            $this->deliveryDays === 1 ? 'den' : ($this->deliveryDays < 5 ? 'dny' : 'dní'),
        );
    }

    public function equals(self $other): bool
    {
        return $this->carrier === $other->carrier
            && $this->priceInCents === $other->priceInCents
            && $this->deliveryDays === $other->deliveryDays;
    }
}
