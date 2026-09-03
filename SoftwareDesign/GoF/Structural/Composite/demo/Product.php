<?php

declare(strict_types=1);

/**
 * LIST — nemá potomky a nikdy mít nebude.
 *
 * Odpovídá na tytéž otázky jako kategorie, jen triviálně.
 * Právě proto s ním jde zacházet stejně.
 */
final readonly class Product implements CatalogNode
{
    public function __construct(
        private string $name,
        private int $priceInCents,
        private bool $isAvailable = true,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function productCount(): int
    {
        return 1;
    }

    public function lowestPriceInCents(): ?int
    {
        return $this->isAvailable ? $this->priceInCents : null;
    }

    public function render(int $depth = 0): string
    {
        return sprintf(
            "%s· %s  %s\n",
            str_repeat('    ', $depth),
            mb_str_pad($this->name, 24 - $depth * 4),
            $this->isAvailable
                ? number_format($this->priceInCents / 100, 0, ',', ' ') . ' Kč'
                : 'nedostupné',
        );
    }
}
