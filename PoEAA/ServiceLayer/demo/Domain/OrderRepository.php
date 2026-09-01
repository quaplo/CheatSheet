<?php

declare(strict_types=1);

namespace Domain;

interface OrderRepository
{
    public function nextIdentity(): OrderId;

    public function save(Order $order): void;

    public function get(OrderId $id): Order;

    /** @return list<Order> */
    public function all(): array;
}
