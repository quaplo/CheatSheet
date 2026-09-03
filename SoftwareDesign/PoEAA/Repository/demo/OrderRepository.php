<?php

declare(strict_types=1);

/**
 * Repository objednávek.
 *
 * Tvař se, že je to kolekce objednávek v paměti. Že za tím je databáze,
 * soubor nebo cizí API, je věc implementace — volající to nesmí poznat.
 *
 * Tenhle soubor patří do DOMÉNY. Implementace do infrastruktury. Kdyby to
 * bylo naopak, doména by závisela na Doctrine a celý smysl by byl pryč.
 *
 * Metody jsou pojmenované doménově — `unpaidOlderThan()`, ne
 * `findBy(['paid' => 0, 'placed_at' => ...])`. Rozdíl je v tom, že první
 * pojmenovává záměr a druhé jen předává filtr do SQL.
 */
interface OrderRepository
{
    /** Identitu vyrábí repository, ne databáze — agregát je platný od začátku. */
    public function nextIdentity(): OrderId;

    public function save(Order $order): void;

    public function remove(OrderId $id): void;

    /** @throws OrderNotFound */
    public function get(OrderId $id): Order;

    public function find(OrderId $id): ?Order;

    /** @return list<Order> */
    public function unpaidPlacedBefore(DateTimeImmutable $moment): array;

    public function countUnpaid(): int;
}
