<?php

declare(strict_types=1);

namespace Core\UseCases;

use Core\Entities\Order;

/** Rozhraní si určuje vnitřní kruh; implementace patří ven. */
interface OrderRepository
{
    public function find(string $orderId): Order;
}
