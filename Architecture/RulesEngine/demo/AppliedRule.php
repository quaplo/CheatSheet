<?php

declare(strict_types=1);

/** Záznam o jednom pravidle, které sedlo — pro auditní stopu. */
final readonly class AppliedRule
{
    public function __construct(
        public string $name,
        public int $priority,
        public int $discountInCents,
        public bool $wasUsed,
    ) {
    }
}
