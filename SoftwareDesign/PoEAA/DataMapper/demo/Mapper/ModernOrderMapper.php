<?php

declare(strict_types=1);

namespace Mapper;

use Domain\Money;
use Domain\Order;
use Domain\OrderItem;
use Domain\OrderStatus;

/**
 * Mapper pro NOVÉ schéma — tentýž doménový objekt.
 *
 *   orders(id, customer_email, currency, status, placed_at)
 *
 * Rozdíly proti starému:
 *   · anglické názvy
 *   · částky jako INTEGER v haléřích
 *   · stav jako řetězec
 *   · datum v ISO
 *   · celkovou částku NEUKLÁDÁ — počítá se z položek
 *
 * A hlavně: **doménový objekt se kvůli téhle změně nezměnil ani
 * o písmeno.** Přesně to je smysl Data Mapperu.
 */
final readonly class ModernOrderMapper
{
    public function __construct(
        private \PDO $connection,
    ) {
    }

    public static function createSchema(\PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE orders (
                id             TEXT PRIMARY KEY,
                customer_email TEXT NOT NULL,
                currency       TEXT NOT NULL,
                status         TEXT NOT NULL,
                placed_at      TEXT NOT NULL
            )',
        );
        $connection->exec(
            'CREATE TABLE order_items (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id         TEXT    NOT NULL,
                sku              TEXT    NOT NULL,
                product_name     TEXT    NOT NULL,
                unit_price_cents INTEGER NOT NULL,
                quantity         INTEGER NOT NULL
            )',
        );
    }

    public function insert(Order $order): void
    {
        $head = $this->connection->prepare(
            'INSERT INTO orders (id, customer_email, currency, status, placed_at)
             VALUES (:id, :email, :currency, :status, :placed)',
        );

        $head->execute([
            'id' => $order->number,
            'email' => $order->customerEmail,
            'currency' => $order->total()->currency,
            'status' => $order->status()->value,
            'placed' => $order->placedAt->format(\DATE_ATOM),
        ]);

        $item = $this->connection->prepare(
            'INSERT INTO order_items (order_id, sku, product_name, unit_price_cents, quantity)
             VALUES (:order, :sku, :name, :price, :qty)',
        );

        foreach ($order->items() as $line) {
            $item->execute([
                'order' => $order->number,
                'sku' => $line->sku,
                'name' => $line->productName,
                'price' => $line->unitPrice->amountInCents,
                'qty' => $line->quantity,
            ]);
        }
    }

    public function find(string $id): ?Order
    {
        $head = $this->connection->prepare('SELECT * FROM orders WHERE id = :id');
        $head->execute(['id' => $id]);
        $row = $head->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $lines = $this->connection->prepare('SELECT * FROM order_items WHERE order_id = :id');
        $lines->execute(['id' => $id]);

        $items = array_map(
            static fn (array $r): OrderItem => new OrderItem(
                $r['sku'],
                $r['product_name'],
                Money::fromCents((int) $r['unit_price_cents'], $row['currency']),
                (int) $r['quantity'],
            ),
            $lines->fetchAll(\PDO::FETCH_ASSOC),
        );

        return Order::reconstitute(
            $row['id'],
            $row['customer_email'],
            OrderStatus::from($row['status']),
            new \DateTimeImmutable($row['placed_at']),
            $items,
        );
    }
}
