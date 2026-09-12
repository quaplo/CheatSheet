<?php

declare(strict_types=1);

namespace Core\UseCases;

/**
 * Výstupní port.
 *
 * Martin: „We don't want to cheat and pass Entities or Database rows."
 * Proto tu není Order, ale hodnoty — a proto na tom nezávisí
 * žádná vnější vrstva na doméně.
 */
final readonly class ShowOrderResponse
{
    public function __construct(
        public string $orderId,
        public int $totalInCents,
        public int $lineCount,
        public string $status,
        public bool $canBeCancelled,
    ) {
    }
}
