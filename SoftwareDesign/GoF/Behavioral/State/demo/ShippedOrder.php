<?php

declare(strict_types=1);

/** Odeslaná objednávka už jde jen doručit — zrušit se nedá. */
final class ShippedOrder extends OrderState
{
    public function name(): string
    {
        return 'odeslaná';
    }

    public function deliver(): OrderState
    {
        return new DeliveredOrder();
    }
}
