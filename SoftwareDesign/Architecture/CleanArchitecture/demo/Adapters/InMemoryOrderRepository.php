<?php

declare(strict_types=1);

namespace Adapters;

use Core\Entities\Order;
use Core\Entities\OrderLine;
use Core\UseCases\OrderRepository;

/** Vnější kruh smí znát vnitřní. Obráceně ne. */
final class InMemoryOrderRepository implements OrderRepository
{
    public function find(string $orderId): Order
    {
        return new Order(
            $orderId,
            [new OrderLine('KNIHA-1', 12900, 2), new OrderLine('KNIHA-7', 4900, 1)],
            'paid',
        );
    }
}
