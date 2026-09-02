<?php

declare(strict_types=1);

namespace Persistence;

use Domain\Order;

/**
 * Úložiště s OPTIMISTICKÝM zámkem.
 *
 * Celý pattern je v jednom `AND version = :v` a v kontrole toho,
 * kolik řádků se změnilo:
 *
 *   1 řádek → nikdo mezitím nezasáhl, hotovo
 *   0 řádků → verze nesedí, někdo byl rychlejší
 *
 * Žádné zámky se nedrží, nic se neblokuje. Konflikty se nepředchází,
 * jen se POZNAJÍ. Proto „optimistický“: předpokládá, že jsou vzácné.
 */
final readonly class VersionedOrderStore
{
    public function __construct(
        private \PDO $connection,
    ) {
    }

    public static function createSchema(\PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE orders_versioned (
                cislo    TEXT PRIMARY KEY,
                note     TEXT    NOT NULL,
                priority TEXT    NOT NULL,
                version  INTEGER NOT NULL
            )',
        );
    }

    public function insert(Order $order): void
    {
        $this->connection
            ->prepare('INSERT INTO orders_versioned (cislo, note, priority, version) VALUES (:c, :n, :p, 1)')
            ->execute(['c' => $order->number, 'n' => $order->note(), 'p' => $order->priority()]);
    }

    public function get(string $number): Order
    {
        $statement = $this->connection->prepare('SELECT * FROM orders_versioned WHERE cislo = :c');
        $statement->execute(['c' => $number]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return Order::reconstitute($row['cislo'], $row['note'], $row['priority'], (int) $row['version']);
    }

    /** @throws ConcurrentModification */
    public function save(Order $order): void
    {
        $statement = $this->connection->prepare(
            'UPDATE orders_versioned
                SET note = :n, priority = :p, version = version + 1
              WHERE cislo = :c AND version = :v',
        );

        $statement->execute([
            'c' => $order->number,
            'n' => $order->note(),
            'p' => $order->priority(),
            'v' => $order->version,
        ]);

        if ($statement->rowCount() === 0) {
            throw ConcurrentModification::of($order->number, $order->version, $this->currentVersion($order->number));
        }
    }

    public function currentVersion(string $number): int
    {
        $statement = $this->connection->prepare('SELECT version FROM orders_versioned WHERE cislo = :c');
        $statement->execute(['c' => $number]);

        return (int) $statement->fetchColumn();
    }
}
