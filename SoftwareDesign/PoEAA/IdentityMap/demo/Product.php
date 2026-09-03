<?php

declare(strict_types=1);

/**
 * Doménová entita — má identitu, mění se v čase.
 *
 * O Identity Map neví vůbec nic. To je záměr: mapa je věc
 * persistenční vrstvy, doména ji nesmí znát.
 */
final class Product
{
    public function __construct(
        public readonly string $sku,
        private string $name,
        private int $priceInCents,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function priceInCents(): int
    {
        return $this->priceInCents;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function changePrice(int $priceInCents): void
    {
        $this->priceInCents = $priceInCents;
    }

    public function format(): string
    {
        return sprintf('%s — %s za %s', $this->sku, $this->name, formatPrice($this->priceInCents));
    }
}

function formatPrice(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}
