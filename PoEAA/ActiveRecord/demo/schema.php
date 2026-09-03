<?php

declare(strict_types=1);

/**
 * Schéma a testovací data. Databáze je v paměti — nic se nikam nezapíše.
 */
function createDatabase(): PDO
{
    $connection = new PDO('sqlite::memory:');
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $connection->exec(
        'CREATE TABLE customers (
            id    TEXT PRIMARY KEY,
            name  TEXT NOT NULL,
            email TEXT NOT NULL
        )',
    );

    $connection->exec(
        'CREATE TABLE orders (
            number      TEXT PRIMARY KEY,
            customer_id TEXT    NOT NULL,
            total_cents INTEGER NOT NULL,
            status      TEXT    NOT NULL
        )',
    );

    $customers = [
        ['alice', 'Alice Nováková', 'alice@example.com'],
        ['bob', 'Bob Dvořák', 'bob@example.com'],
        ['carol', 'Carol Svobodová', 'carol@example.com'],
    ];

    foreach ($customers as [$id, $name, $email]) {
        $connection->prepare('INSERT INTO customers VALUES (?, ?, ?)')->execute([$id, $name, $email]);
    }

    $orders = [
        ['2024/001', 'alice', 129000, 'nová'],
        ['2024/002', 'bob', 749000, 'potvrzená'],
        ['2024/003', 'alice', 249000, 'odeslaná'],
        ['2024/004', 'carol', 89000, 'nová'],
        ['2024/005', 'bob', 199000, 'nová'],
    ];

    foreach ($orders as [$number, $customerId, $total, $status]) {
        $connection->prepare('INSERT INTO orders VALUES (?, ?, ?, ?)')->execute([$number, $customerId, $total, $status]);
    }

    return $connection;
}
