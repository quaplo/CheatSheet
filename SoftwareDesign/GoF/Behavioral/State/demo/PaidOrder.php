<?php

declare(strict_types=1);

/**
 * Zaplacená objednávka: jde odeslat nebo zrušit.
 *
 * Zrušení zaplacené objednávky znamená vratku — a přesně tohle je důvod,
 * proč stav není jen nálepka. Kdyby to byl `switch`, ta znalost by ležela
 * někde v use-case; tady je u stavu, kterého se týká.
 */
final class PaidOrder extends OrderState
{
    public function name(): string
    {
        return 'zaplacená';
    }

    public function ship(): OrderState
    {
        return new ShippedOrder();
    }

    public function cancel(): OrderState
    {
        return new CancelledOrder(refundRequired: true);
    }
}
