<?php

declare(strict_types=1);

namespace Core\Port\Driven;

use Core\Domain\Order;

/**
 * Řízený (secondary) port pro persistenci.
 *
 * Metody jsou v pojmech domény — save(), findByNumber(). Žádné flush(),
 * createQueryBuilder() ani getEntityManager(); to by sem protáhlo Doctrine
 * a port by přestal být portem.
 */
interface OrderRepository
{
    public function nextNumber(): string;

    public function save(Order $order): void;

    public function findByNumber(string $number): ?Order;

    /** @return list<Order> */
    public function all(): array;
}
