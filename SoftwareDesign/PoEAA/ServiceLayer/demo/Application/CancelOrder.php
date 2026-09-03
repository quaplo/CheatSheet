<?php

declare(strict_types=1);

namespace Application;

final readonly class CancelOrder
{
    public function __construct(
        public string $orderId,
        public string $reason,
    ) {
    }
}
