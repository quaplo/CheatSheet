<?php

declare(strict_types=1);

/**
 * Databáze v paměti. Drží se tu i historie schématu, aby bylo vidět,
 * v jaké fázi migrace se právě nacházíme.
 */
final class Database
{
    public readonly PDO $pdo;

    public function __construct()
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE orders (
                number  TEXT PRIMARY KEY,
                shipped INTEGER NOT NULL DEFAULT 0
            )',
        );
    }

    /** @return list<string> */
    public function columns(): array
    {
        $rows = $this->pdo->query('PRAGMA table_info(orders)')->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static fn (array $r): string => (string) $r['name'], $rows);
    }

    public function hasColumn(string $name): bool
    {
        return in_array($name, $this->columns(), true);
    }

    public function count(string $where = '1=1'): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM orders WHERE ' . $where)->fetchColumn();
    }
}
