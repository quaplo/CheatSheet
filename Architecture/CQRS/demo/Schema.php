<?php

declare(strict_types=1);

/** Jedno schéma pro obě strany — tenhle stupeň CQRS nepotřebuje dvě databáze. */
final class Schema
{
    public static function create(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE orders (
                id             TEXT PRIMARY KEY,
                customer_email TEXT NOT NULL,
                status         TEXT NOT NULL,
                placed_at      TEXT NOT NULL
            )',
        );

        $connection->exec(
            'CREATE TABLE order_items (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id         TEXT    NOT NULL,
                product          TEXT    NOT NULL,
                unit_price_cents INTEGER NOT NULL,
                quantity         INTEGER NOT NULL
            )',
        );

        // Index pro čtecí stranu. Čtecí model se optimalizuje nezávisle
        // na doméně — to je jeden z hlavních důvodů, proč se odděluje.
        $connection->exec('CREATE INDEX idx_items_order ON order_items (order_id)');
        $connection->exec('CREATE INDEX idx_orders_placed ON orders (placed_at DESC)');
    }
}
