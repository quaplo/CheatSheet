<?php

declare(strict_types=1);

/**
 * Převod starých událostí na dnešní tvar — „upcasting".
 *
 * Je to jediný způsob, jak měnit schéma událostí: původní záznam
 * zůstane, jak byl, a při čtení se doplní, co dnešní kód čeká.
 */
final class Upcaster
{
    /**
     * @param list<Event> $events
     * @return list<Event>
     */
    public static function upcast(array $events): array
    {
        return array_map(
            static fn (Event $event): Event => $event instanceof LegacyItemAdded
                ? new ItemAdded($event->sku, $event->priceInCents, 1, $event->happenedAt())
                : $event,
            $events,
        );
    }
}
