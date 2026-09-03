<?php

declare(strict_types=1);

/** Koncový stav. Nepřepisuje nic, takže je zakázané všechno. */
final class DeliveredOrder extends OrderState
{
    public function name(): string
    {
        return 'doručená';
    }
}
