<?php

declare(strict_types=1);

namespace Application;

use Domain\DomainEvent;
use Domain\Order;

/**
 * Zjednodušené úložiště s transakcí.
 *
 * Podstatné je, že události se z agregátu vyberou při uložení a drží
 * se stranou, dokud transakce neskončí. Když se transakce vrátí zpět,
 * zahodí se s ní — protože se nic nestalo, a tedy není co oznamovat.
 *
 * V Doctrine tohle dělá posluchač na `postFlush`, v Symfony Messenger
 * middleware `DispatchAfterCurrentBus`.
 */
final class OrderStore
{
    /** @var array<string, Order> */
    private array $committed = [];

    /** @var array<string, Order> */
    private array $pending = [];

    /** @var list<DomainEvent> */
    private array $pendingEvents = [];

    /** @var list<DomainEvent> */
    private array $readyToPublish = [];

    public function save(Order $order): void
    {
        $this->pending[$order->id] = $order;

        foreach ($order->releaseEvents() as $event) {
            $this->pendingEvents[] = $event;
        }
    }

    /** @param callable(): mixed $work */
    public function transactional(callable $work): mixed
    {
        $this->pending = [];
        $this->pendingEvents = [];

        try {
            $result = $work();
        } catch (\Throwable $e) {
            // ROLLBACK — zahodíme změny i události.
            $this->pending = [];
            $this->pendingEvents = [];

            throw $e;
        }

        // COMMIT — teprve teď jsou události skutečnými fakty.
        foreach ($this->pending as $id => $order) {
            $this->committed[$id] = $order;
        }

        $this->readyToPublish = $this->pendingEvents;
        $this->pending = [];
        $this->pendingEvents = [];

        return $result;
    }

    /** @return list<DomainEvent> */
    public function releaseRecordedEvents(): array
    {
        $events = $this->readyToPublish;
        $this->readyToPublish = [];

        return $events;
    }

    public function count(): int
    {
        return count($this->committed);
    }
}
