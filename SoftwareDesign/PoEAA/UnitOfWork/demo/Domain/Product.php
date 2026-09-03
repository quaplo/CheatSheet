<?php

declare(strict_types=1);

namespace Domain;

/**
 * Doménový objekt. O žádné Unit of Work neví — a to je podstatné.
 *
 * Nikde tu není `save()` ani `markDirty()`. Objekt se prostě mění
 * a někdo jiný si všimne, že se změnil.
 */
final class Product
{
    public function __construct(
        public readonly string $sku,
        private string $name,
        private int $priceInCents,
        private int $stock,
    ) {
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function changePrice(int $priceInCents): void
    {
        if ($priceInCents <= 0) {
            throw new \DomainException('Cena musí být kladná.');
        }

        $this->priceInCents = $priceInCents;
    }

    public function reserve(int $quantity): void
    {
        if ($quantity > $this->stock) {
            throw new \DomainException(sprintf('Skladem je jen %d ks %s.', $this->stock, $this->sku));
        }

        $this->stock -= $quantity;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function priceInCents(): int
    {
        return $this->priceInCents;
    }

    public function stock(): int
    {
        return $this->stock;
    }

    /** @return array{sku: string, name: string, price: int, stock: int} */
    public function snapshot(): array
    {
        return ['sku' => $this->sku, 'name' => $this->name, 'price' => $this->priceInCents, 'stock' => $this->stock];
    }
}
