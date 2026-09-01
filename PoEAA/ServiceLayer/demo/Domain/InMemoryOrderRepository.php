<?php

declare(strict_types=1);

namespace Domain;

final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function nextIdentity(): OrderId
    {
        return OrderId::generate();
    }

    public function save(Order $order): void
    {
        $this->orders[$order->id->value] = $order;
    }

    public function get(OrderId $id): Order
    {
        return $this->orders[$id->value] ?? throw new \RuntimeException('Objednávka neexistuje.');
    }

    public function all(): array
    {
        return array_values($this->orders);
    }
}
