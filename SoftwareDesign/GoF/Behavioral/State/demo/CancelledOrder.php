<?php

declare(strict_types=1);

/**
 * Koncový stav.
 *
 * Stav může nést i data — jestli se má vracet zaplacená částka, závisí
 * na tom, odkud se sem přišlo. To je zároveň hranice, za kterou už enum
 * nestačí: enum je hodnota, tohle je objekt s vlastním obsahem.
 */
final class CancelledOrder extends OrderState
{
    public function __construct(
        public readonly bool $refundRequired = false,
    ) {
    }

    public function name(): string
    {
        return 'zrušená';
    }
}
