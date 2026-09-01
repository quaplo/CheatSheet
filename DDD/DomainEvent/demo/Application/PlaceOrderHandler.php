<?php

declare(strict_types=1);

namespace Application;

use Domain\Order;

/**
 * Use-case. Má DVĚ závislosti, ne šest.
 *
 * Kdyby se reakce volaly přímo, musel by tenhle handler znát mailer,
 * sklad, statistiky a všechno, co kdy někdo přidá — a při každé nové
 * reakci by se do něj sahalo.
 *
 * Klíčové je pořadí v metodě place(): nejdřív ULOŽIT, potom
 * publikovat. Nikdy naopak.
 */
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderStore $orders,
        private EventDispatcher $dispatcher,
    ) {
    }

    /** @param list<array{sku: string, quantity: int}> $items */
    public function place(
        string $id,
        string $email,
        int $totalInCents,
        array $items,
        \DateTimeImmutable $now,
    ): void {
        $this->orders->transactional(function () use ($id, $email, $totalInCents, $items, $now): Order {
            $order = Order::place($id, $email, $totalInCents, $items, $now);

            $this->orders->save($order);

            return $order;
        });

        // Až sem se dostaneme jen po úspěšném commitu.
        $this->dispatcher->dispatchAll($this->orders->releaseRecordedEvents());
    }
}
