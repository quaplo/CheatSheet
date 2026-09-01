<?php

declare(strict_types=1);

namespace Application;

use Domain\DomainEvent;

/**
 * Rozesílání událostí posluchačům.
 *
 * V Symfony by tohle byl EventDispatcher nebo Messenger; tady je to
 * dvacet řádků, aby bylo vidět, že na patternu není nic magického.
 */
final class EventDispatcher
{
    /** @var array<class-string, list<callable>> */
    private array $listeners = [];

    /** @param class-string $eventClass */
    public function listen(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    /** @param list<DomainEvent> $events */
    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            foreach ($this->listeners[$event::class] ?? [] as $listener) {
                $listener($event);
            }
        }
    }
}
