<?php

declare(strict_types=1);

namespace Application;

use Domain\OrderId;
use Domain\OrderRepository;

/**
 * Druhý use-case — a všimni si, že má JINÉ závislosti.
 *
 * Nepotřebuje úvěrový limit, tak si o něj neřekne. V jedné velké
 * službě by ho dostal taky, protože konstruktor je společný.
 */
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private EventPublisher $events,
    ) {
    }

    public function handle(CancelOrder $command): void
    {
        $order = $this->orders->get(OrderId::fromString($command->orderId));

        $order->cancel();

        $this->orders->save($order);

        $this->events->publish('order.cancelled', [
            'orderId' => $command->orderId,
            'reason' => $command->reason,
        ]);
    }
}
