<?php

declare(strict_types=1);

namespace Adapter\Driven\Persistence;

use Core\Domain\Order;
use Core\Port\Driven\OrderRepository;

/**
 * Řízený adaptér pro testy.
 *
 * Není to podvod ani zjednodušení pro ukázku — tohle je jeden z hlavních
 * důvodů, proč se porty zavádějí. Test se obejde bez databáze, protože
 * jádru je jedno, kdo port implementuje.
 */
final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> */
    private array $orders = [];

    private int $counter = 0;

    public function nextNumber(): string
    {
        return sprintf('OBJ-%03d', ++$this->counter);
    }

    public function save(Order $order): void
    {
        $this->orders[$order->number] = $order;
    }

    public function findByNumber(string $number): ?Order
    {
        return $this->orders[$number] ?? null;
    }

    public function all(): array
    {
        return array_values($this->orders);
    }
}
