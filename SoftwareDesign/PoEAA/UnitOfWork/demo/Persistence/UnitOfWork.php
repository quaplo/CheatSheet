<?php

declare(strict_types=1);

namespace Persistence;

use Domain\Product;

/**
 * UNIT OF WORK.
 *
 * Drží seznam objektů, se kterými se během operace pracovalo,
 * a **na konci se rozhodne, co je opravdu potřeba zapsat**.
 *
 * Tři věci, které z toho plynou a které dohromady tvoří pattern:
 *
 *   1. IDENTITY MAP — týž záznam načtený dvakrát je tentýž objekt.
 *      Bez toho by dvě části kódu měly dvě kopie a jedna by druhou
 *      přepsala.
 *
 *   2. SLEDOVÁNÍ ZMĚN — při načtení se uloží snímek. Na konci se
 *      porovná se současným stavem; co se nezměnilo, se nezapisuje.
 *
 *   3. JEDEN COMMIT — všechny zápisy proběhnou najednou v transakci.
 *      Buď všechno, nebo nic.
 *
 * Tohle přesně dělá Doctrine, když zavoláš flush().
 */
final class UnitOfWork
{
    /** @var array<string, Product> identity map */
    private array $identityMap = [];

    /** @var array<string, array<string, mixed>> snímky při načtení */
    private array $originals = [];

    /** @var list<Product> nově vytvořené */
    private array $new = [];

    public int $reads = 0;
    public int $writes = 0;

    public function __construct(
        private readonly \PDO $connection,
    ) {
    }

    public function find(string $sku): ?Product
    {
        // Identity map: podruhé už do databáze nechodíme.
        if (isset($this->identityMap[$sku])) {
            return $this->identityMap[$sku];
        }

        $this->reads++;

        $statement = $this->connection->prepare('SELECT * FROM products WHERE sku = :sku');
        $statement->execute(['sku' => $sku]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $product = new Product($row['sku'], $row['name'], (int) $row['price'], (int) $row['stock']);

        $this->identityMap[$sku] = $product;
        $this->originals[$sku] = $product->snapshot();   // ← snímek pro pozdější porovnání

        return $product;
    }

    public function register(Product $product): void
    {
        $this->new[] = $product;
        $this->identityMap[$product->sku] = $product;
    }

    /**
     * Zapíše všechno naráz — a jen to, co se opravdu změnilo.
     *
     * @return array{inserted: int, updated: int, unchanged: int}
     */
    public function commit(): array
    {
        $inserted = 0;
        $updated = 0;
        $unchanged = 0;

        $this->connection->beginTransaction();

        try {
            foreach ($this->new as $product) {
                $this->insert($product);
                $this->originals[$product->sku] = $product->snapshot();
                $inserted++;
            }

            foreach ($this->identityMap as $sku => $product) {
                if (in_array($product, $this->new, strict: true)) {
                    continue;
                }

                if ($product->snapshot() === $this->originals[$sku]) {
                    $unchanged++;      // ← beze změny, nezapisujeme

                    continue;
                }

                $this->update($product);
                $this->originals[$sku] = $product->snapshot();
                $updated++;
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }

        $this->new = [];

        return ['inserted' => $inserted, 'updated' => $updated, 'unchanged' => $unchanged];
    }

    private function insert(Product $product): void
    {
        $this->writes++;
        $this->connection
            ->prepare('INSERT INTO products (sku, name, price, stock) VALUES (:sku, :n, :p, :s)')
            ->execute(['sku' => $product->sku, 'n' => $product->name(), 'p' => $product->priceInCents(), 's' => $product->stock()]);
    }

    private function update(Product $product): void
    {
        $this->writes++;
        $this->connection
            ->prepare('UPDATE products SET name = :n, price = :p, stock = :s WHERE sku = :sku')
            ->execute(['sku' => $product->sku, 'n' => $product->name(), 'p' => $product->priceInCents(), 's' => $product->stock()]);
    }
}
