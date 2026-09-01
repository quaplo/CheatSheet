<?php

declare(strict_types=1);

/** Nová objednávka: jde zaplatit nebo zrušit. */
final class NewOrder extends OrderState
{
    public function name(): string
    {
        return 'nová';
    }

    public function pay(): OrderState
    {
        return new PaidOrder();
    }

    public function cancel(): OrderState
    {
        return new CancelledOrder();
    }
}
