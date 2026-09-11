<?php

declare(strict_types=1);

/**
 * Objednávka poskládaná z událostí.
 *
 * Nedrží si stav jako pravdu — drží si ho jako mezivýsledek
 * přehrání proudu. Pravda je ten proud.
 */
final class Order
{
    private string $shippingAddress = '';

    private int $itemsTotalInCents = 0;

    private ?string $carrier = null;

    /** @var list<Event> */
    private array $stream = [];

    /** @param list<Event> $events */
    public static function replay(array $events, ShippingRules $rules): self
    {
        $order = new self();

        foreach ($events as $event) {
            $order->apply($event, $rules);
            $order->stream[] = $event;
        }

        return $order;
    }

    private function apply(Event $event, ShippingRules $rules): void
    {
        match (true) {
            $event instanceof OrderPlaced => $this->shippingAddress = $event->shippingAddress,
            $event instanceof ItemAdded => $this->itemsTotalInCents += $event->priceInCents * $event->quantity,
            $event instanceof AddressChanged => $this->shippingAddress = $event->shippingAddress,
            $event instanceof OrderShipped => $this->carrier = $event->carrier,
            default => null,
        };
    }

    public function shippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function totalInCents(ShippingRules $rules): int
    {
        return $this->itemsTotalInCents + $rules->shippingFor($this->itemsTotalInCents);
    }

    /** Kolikrát se během života objednávky stalo tohle. */
    public function countOf(string $eventClass): int
    {
        return count(array_filter($this->stream, static fn (Event $e): bool => $e instanceof $eventClass));
    }

    /** Jak vypadal stav v okamžiku, kdy se stala daná událost. */
    public function addressWhen(string $eventClass, ShippingRules $rules): ?string
    {
        $upTo = [];

        foreach ($this->stream as $event) {
            $upTo[] = $event;

            if ($event instanceof $eventClass) {
                return self::replay($upTo, $rules)->shippingAddress();
            }
        }

        return null;
    }

    /** @return list<Event> */
    public function stream(): array
    {
        return $this->stream;
    }
}
