<?php

declare(strict_types=1);

namespace Persistence;

use Domain\Product;

/**
 * BEZ Unit of Work: každá změna se ukládá hned.
 *
 * Vypadá to nejjednodušeji a v malém to funguje. Tři problémy
 * se ale objeví spolehlivě:
 *
 *   1. Zápisů je tolik, kolik je změn — ne kolik je objektů.
 *   2. Když operace uprostřed selže, půlka změn už je v databázi.
 *   3. Volající si musí pamatovat, že po každé změně má uložit.
 */
final class ImmediateProductStore
{
    public int $writes = 0;

    public function __construct(
        private readonly \PDO $connection,
    ) {
    }

    public function save(Product $product): void
    {
        $this->writes++;

        $this->connection
            ->prepare('UPDATE products SET name = :n, price = :p, stock = :s WHERE sku = :sku')
            ->execute([
                'sku' => $product->sku,
                'n' => $product->name(),
                'p' => $product->priceInCents(),
                's' => $product->stock(),
            ]);
    }
}
