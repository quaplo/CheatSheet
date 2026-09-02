<?php

declare(strict_types=1);

/**
 * Zástupce databáze — drží řádky a počítá dotazy.
 *
 * Řádek je pole, ne objekt. Právě proto může každé načtení
 * vyrobit novou instanci — a právě to Identity Map řeší.
 */
final class ProductStorage
{
    /** @var array<string, array{name: string, price: int}> */
    private array $rows = [];

    public int $queryCount = 0;

    public function insert(string $sku, string $name, int $priceInCents): void
    {
        $this->rows[$sku] = ['name' => $name, 'price' => $priceInCents];
    }

    /** @return array{name: string, price: int} */
    public function fetchRow(string $sku): array
    {
        ++$this->queryCount;

        if (!isset($this->rows[$sku])) {
            throw new RuntimeException(sprintf('Produkt %s neexistuje.', $sku));
        }

        return $this->rows[$sku];
    }

    public function update(Product $product): void
    {
        $this->rows[$product->sku] = [
            'name' => $product->name(),
            'price' => $product->priceInCents(),
        ];
    }

    /** @return array{name: string, price: int} čte přímo, bez počítání dotazů */
    public function peek(string $sku): array
    {
        return $this->rows[$sku];
    }

    public function resetQueryCount(): void
    {
        $this->queryCount = 0;
    }
}
