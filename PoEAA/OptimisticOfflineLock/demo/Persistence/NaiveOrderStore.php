<?php

declare(strict_types=1);

namespace Persistence;

use Domain\Order;

/**
 * Úložiště BEZ zámku — tak, jak to vypadá ve většině aplikací.
 *
 * `UPDATE … WHERE cislo = ?` prostě přepíše, co tam je. Když mezitím
 * někdo jiný uložil svou verzi, jeho změna zmizí — bez chyby, bez
 * varování, bez stopy v logu.
 */
final readonly class NaiveOrderStore
{
    public function __construct(
        private \PDO $connection,
    ) {
    }

    public static function createSchema(\PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE orders_naive (
                cislo    TEXT PRIMARY KEY,
                note     TEXT NOT NULL,
                priority TEXT NOT NULL
            )',
        );
    }

    public function insert(Order $order): void
    {
        $this->connection
            ->prepare('INSERT INTO orders_naive (cislo, note, priority) VALUES (:c, :n, :p)')
            ->execute(['c' => $order->number, 'n' => $order->note(), 'p' => $order->priority()]);
    }

    public function get(string $number): Order
    {
        $statement = $this->connection->prepare('SELECT * FROM orders_naive WHERE cislo = :c');
        $statement->execute(['c' => $number]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return Order::reconstitute($row['cislo'], $row['note'], $row['priority'], version: 1);
    }

    public function save(Order $order): void
    {
        $this->connection
            ->prepare('UPDATE orders_naive SET note = :n, priority = :p WHERE cislo = :c')
            ->execute(['c' => $order->number, 'n' => $order->note(), 'p' => $order->priority()]);
    }
}
