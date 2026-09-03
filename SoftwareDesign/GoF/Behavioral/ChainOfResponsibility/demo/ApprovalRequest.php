<?php

declare(strict_types=1);

/** Žádost o schválení mimořádné slevy. */
final readonly class ApprovalRequest
{
    public function __construct(
        public string $orderNumber,
        public int $discountInCents,
        public string $requestedBy,
    ) {
    }
}
