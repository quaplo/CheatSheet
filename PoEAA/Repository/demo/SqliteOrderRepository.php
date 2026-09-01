<?php

declare(strict_types=1);

/**
 * Implementace nad SQL.
 *
 * Tady a nikde jinde žije znalost o tabulkách, sloupcích a typech.
 * Volající vidí pořád jen OrderRepository.
 *
 * Všimni si dvou soukromých metod na konci — mapování doména ↔ řádek.
 * To je ta „data mapping layer“, kterou Fowler v definici zmiňuje: agregát
 * nemá tušení, že existuje sloupec `total_cents`.
 */
final class SqliteOrderRepository implements OrderRepository
{
    public function __construct(
        private readonly PDO $connection,
    ) {
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS orders (
                id             TEXT PRIMARY KEY,
                customer_email TEXT    NOT NULL,
                total_cents    INTEGER NOT NULL,
                is_paid        INTEGER NOT NULL,
                placed_at      TEXT    NOT NULL
            )',
        );
    }

    public function nextIdentity(): OrderId
    {
        // Identita nevzniká v databázi — nepotřebujeme INSERT, abychom ji znali.
        return OrderId::generate();
    }

    public function save(Order $order): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO orders (id, customer_email, total_cents, is_paid, placed_at)
             VALUES (:id, :email, :total, :paid, :placed)
             ON CONFLICT(id) DO UPDATE SET
                customer_email = excluded.customer_email,
                total_cents    = excluded.total_cents,
                is_paid        = excluded.is_paid,
                placed_at      = excluded.placed_at',
        );

        $statement->execute($this->toRow($order));
    }

    public function remove(OrderId $id): void
    {
        $statement = $this->connection->prepare('DELETE FROM orders WHERE id = :id');
        $statement->execute(['id' => $id->value]);
    }

    public function get(OrderId $id): Order
    {
        return $this->find($id) ?? throw OrderNotFound::withId($id);
    }

    public function find(OrderId $id): ?Order
    {
        $statement = $this->connection->prepare('SELECT * FROM orders WHERE id = :id');
        $statement->execute(['id' => $id->value]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->toOrder($row);
    }

    public function unpaidPlacedBefore(DateTimeImmutable $moment): array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM orders WHERE is_paid = 0 AND placed_at < :moment ORDER BY placed_at',
        );
        $statement->execute(['moment' => $moment->format('Y-m-d H:i:s')]);

        return array_map($this->toOrder(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countUnpaid(): int
    {
        // Počítání patří do databáze. Načíst všechno do PHP a spočítat
        // to tam by byla přesně ta chyba, které se repository má vyhnout.
        return (int) $this->connection
            ->query('SELECT COUNT(*) FROM orders WHERE is_paid = 0')
            ->fetchColumn();
    }

    /** @return array<string, string|int> doména → řádek */
    private function toRow(Order $order): array
    {
        return [
            'id' => $order->id->value,
            'email' => $order->customerEmail,
            'total' => $order->totalInCents,
            'paid' => $order->isPaid ? 1 : 0,
            'placed' => $order->placedAt->format('Y-m-d H:i:s'),
        ];
    }

    /** @param array<string, mixed> $row řádek → doména */
    private function toOrder(array $row): Order
    {
        return Order::reconstitute(
            OrderId::fromString((string) $row['id']),
            (string) $row['customer_email'],
            (int) $row['total_cents'],
            (bool) $row['is_paid'],
            new DateTimeImmutable((string) $row['placed_at']),
        );
    }
}
