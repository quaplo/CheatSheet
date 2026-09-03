<?php

declare(strict_types=1);

/**
 * Zpracování příkazu.
 *
 * Vrací jen identitu vytvořeného agregátu — nic k zobrazení. Kdyby
 * vracel data pro obrazovku, začalo by se ze zápisové strany stávat
 * i čtení a rozdělení by ztratilo smysl.
 */
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
    ) {
    }

    public function handle(PlaceOrder $command, DateTimeImmutable $now): string
    {
        $order = Order::place(
            $this->orders->nextIdentity(),
            $command->customerEmail,
            $command->items,
            $now,
        );

        $this->orders->save($order);

        return $order->id;
    }
}
