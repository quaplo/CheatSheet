<?php

declare(strict_types=1);

namespace Application;

use Domain\Order;
use Domain\OrderRepository;

/**
 * USE-CASE. Jedna třída, jedna operace.
 *
 * Podívej se, co dělá — je to pět kroků a ani jeden z nich není
 * byznysové rozhodnutí:
 *
 *   1. posbírá, co doména potřebuje (limit z jiného agregátu)
 *   2. otevře transakci
 *   3. nechá doménu rozhodnout
 *   4. uloží
 *   5. po commitu publikuje, co se stalo
 *
 * Kdyby v téhle třídě byl `if` o tom, KDY se objednávka smí založit,
 * je to signál, že pravidlo patří o vrstvu níž.
 */
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private CustomerCredit $credit,
        private EventPublisher $events,
    ) {
    }

    public function handle(PlaceOrder $command): string
    {
        // 1. Doména si o cizí agregát neříká sama — dostane ho hotový.
        $limit = $this->credit->limitFor($command->customerId);

        // 2. + 3. + 4.
        $order = Order::place(
            $this->orders->nextIdentity(),
            $command->customerId,
            $command->totalInCents,
            $limit,
        );

        $this->orders->save($order);

        // 5. Až po uložení.
        $this->events->publish('order.placed', ['orderId' => $order->id->value]);

        // Ven jde identita, ne agregát.
        return $order->id->value;
    }
}
