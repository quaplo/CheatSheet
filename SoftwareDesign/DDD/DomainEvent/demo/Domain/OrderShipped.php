<?php

declare(strict_types=1);

namespace Domain;

final readonly class OrderShipped implements DomainEvent
{
    public function __construct(
        public string $orderId,
        public string $trackingNumber,
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
