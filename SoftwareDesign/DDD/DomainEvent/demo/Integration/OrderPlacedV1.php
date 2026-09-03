<?php

declare(strict_types=1);

namespace Integration;

use Domain\OrderPlaced;

/**
 * INTEGRAČNÍ událost — ta, která opouští bounded context.
 *
 * Není to táž věc jako doménová událost, i když popisuje totéž.
 * Rozdíly, které rozhodují:
 *
 *   · má VERZI, protože je to veřejný kontrakt
 *   · nese jen to, co cizí služby potřebují (žádný e-mail zákazníka)
 *   · nemění se, když se změní náš vnitřní model
 *
 * Kdyby se ven publikovala rovnou doménová událost, stal by se
 * z našeho vnitřního modelu veřejné API, které nejde měnit.
 */
final readonly class OrderPlacedV1
{
    public function __construct(
        public string $eventId,
        public string $orderId,
        public int $totalInCents,
        public int $itemCount,
        public string $occurredAt,
    ) {
    }

    public static function fromDomainEvent(OrderPlaced $event): self
    {
        return new self(
            eventId: 'EVT-' . strtoupper(bin2hex(random_bytes(4))),
            orderId: $event->orderId,
            totalInCents: $event->totalInCents,
            itemCount: count($event->items),
            occurredAt: $event->occurredAt()->format(\DATE_ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toMessage(): array
    {
        return [
            'type' => 'order.placed',
            'version' => 1,
            'id' => $this->eventId,
            'occurredAt' => $this->occurredAt,
            'data' => [
                'orderId' => $this->orderId,
                'totalInCents' => $this->totalInCents,
                'itemCount' => $this->itemCount,
            ],
        ];
    }
}
