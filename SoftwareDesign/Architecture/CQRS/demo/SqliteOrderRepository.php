<?php

declare(strict_types=1);

/**
 * Implementace zápisové strany.
 *
 * Načtení agregátu stojí dva dotazy: objednávka a její položky. Pro
 * změnu jedné objednávky je to v pořádku. Pro výpis dvaceti řádků
 * v administraci je to čtyřicet dotazů — a to je celý důvod, proč
 * existuje čtecí strana.
 */
final readonly class SqliteOrderRepository implements OrderRepository
{
    public function __construct(
        private PDO $connection,
    ) {
    }

    public function nextIdentity(): string
    {
        return strtoupper(bin2hex(random_bytes(4)));
    }

    public function save(Order $order): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO orders (id, customer_email, status, placed_at)
             VALUES (:id, :email, :status, :placed)
             ON CONFLICT(id) DO UPDATE SET status = excluded.status',
        );

        $statement->execute([
            'id' => $order->id,
            'email' => $order->customerEmail,
            'status' => $order->status,
            'placed' => $order->placedAt->format('Y-m-d H:i:s'),
        ]);

        $items = $this->connection->prepare(
            'INSERT INTO order_items (order_id, product, unit_price_cents, quantity)
             VALUES (:order, :product, :price, :quantity)',
        );

        foreach ($order->items as $item) {
            $items->execute([
                'order' => $order->id,
                'product' => $item->product,
                'price' => $item->unitPriceInCents,
                'quantity' => $item->quantity,
            ]);
        }
    }

    public function get(string $id): Order
    {
        $head = $this->connection->prepare('SELECT * FROM orders WHERE id = :id');
        $head->execute(['id' => $id]);
        $row = $head->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException(sprintf('Objednávka %s neexistuje.', $id));
        }

        $itemRows = $this->connection->prepare('SELECT * FROM order_items WHERE order_id = :id');
        $itemRows->execute(['id' => $id]);

        $items = array_map(
            static fn (array $i): OrderItem => new OrderItem($i['product'], (int) $i['unit_price_cents'], (int) $i['quantity']),
            $itemRows->fetchAll(PDO::FETCH_ASSOC),
        );

        return Order::reconstitute(
            $row['id'],
            $row['customer_email'],
            $row['status'],
            new DateTimeImmutable($row['placed_at']),
            $items,
        );
    }

    /**
     * Metoda, kterou by si vynutil výpis, kdyby čtecí strana neexistovala.
     * Je tu jen pro srovnání v demu — v reálném kódu by tady být neměla.
     *
     * @return list<Order>
     */
    public function allForComparison(): array
    {
        $ids = $this->connection->query('SELECT id FROM orders')->fetchAll(PDO::FETCH_COLUMN);

        return array_map($this->get(...), $ids);
    }
}
