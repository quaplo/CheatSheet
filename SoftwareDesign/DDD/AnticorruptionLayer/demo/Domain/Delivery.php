<?php

declare(strict_types=1);

namespace Domain;

/** Dodávka v našich pojmech. Čistá, bez jediné stopy po ERP. */
final readonly class Delivery
{
    public function __construct(
        public string $number,
        public SupplierId $supplierId,
        public string $supplierName,
        public int $quantity,
        public int $valueInCents,
        public \DateTimeImmutable $deliveredOn,
        public DeliveryStatus $status,
    ) {
    }
}
