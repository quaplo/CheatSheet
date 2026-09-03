<?php

declare(strict_types=1);

namespace Application\Query;

/**
 * Čtecí DTO. Tvar obrazovky, ne doménový objekt.
 *
 * Nemá chování ani pravidla a nikdy je mít nebude — kdyby je měl,
 * byla by to druhá doména, kterou nikdo nehlídá.
 */
final readonly class OrderSummary
{
    public function __construct(
        public string $orderId,
        public int $totalInCents,
        public string $status,
    ) {
    }
}
