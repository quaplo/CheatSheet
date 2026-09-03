<?php

declare(strict_types=1);

/** Základ — ten, kdo skutečně dělá práci. */
final class SqliteProductRepository implements ProductRepository
{
    public int $queries = 0;

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    public function find(string $sku): ?string
    {
        $this->queries++;

        $statement = $this->connection->prepare('SELECT name FROM products WHERE sku = :sku');
        $statement->execute(['sku' => $sku]);

        $name = $statement->fetchColumn();

        return $name === false ? null : (string) $name;
    }
}
