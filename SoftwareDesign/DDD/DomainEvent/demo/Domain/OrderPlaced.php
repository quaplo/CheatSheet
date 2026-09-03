<?php

declare(strict_types=1);

namespace Domain;

/**
 * Vnitřní doménová událost.
 *
 * Nese, co je potřeba k reakci — ne celý agregát. Kdyby v ní byl objekt
 * Order, mohl by ho handler změnit a událost by přestala být faktem.
 */
final readonly class OrderPlaced implements DomainEvent
{
    /** @param list<array{sku: string, quantity: int}> $items */
    public function __construct(
        public string $orderId,
        public string $customerEmail,
        public int $totalInCents,
        public array $items,
        private \DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function aggregateId(): string
    {
        return $this->orderId;
    }
}
