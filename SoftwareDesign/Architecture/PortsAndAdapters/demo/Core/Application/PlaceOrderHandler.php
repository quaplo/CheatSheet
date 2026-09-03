<?php

declare(strict_types=1);

namespace Core\Application;

use Core\Domain\Order;
use Core\Port\Driven\OrderRepository;
use Core\Port\Driven\PaymentGateway;
use Core\Port\Driving\PaymentDeclined;
use Core\Port\Driving\PlaceOrder;
use Core\Port\Driving\PlaceOrderCommand;

/**
 * Use-case. Srdce aplikace.
 *
 * Podívej se na konstruktor: závisí na dvou rozhraních, která si jádro samo
 * definovalo. Nikde tu není Doctrine, HTTP klient ani konfigurace — a proto
 * jde tahle třída spustit v testu bez jediného kusu infrastruktury.
 */
final readonly class PlaceOrderHandler implements PlaceOrder
{
    public function __construct(
        private OrderRepository $orders,
        private PaymentGateway $payments,
    ) {
    }

    public function place(PlaceOrderCommand $command): string
    {
        $order = Order::place(
            $this->orders->nextNumber(),
            $command->customerEmail,
            $command->totalInCents,
        );

        $payment = $this->payments->charge($order->number, $order->totalInCents);

        if ($payment->isApproved === false) {
            throw new PaymentDeclined($payment->message);
        }

        $this->orders->save($order->paidWith((string) $payment->reference));

        return $order->number;
    }
}
