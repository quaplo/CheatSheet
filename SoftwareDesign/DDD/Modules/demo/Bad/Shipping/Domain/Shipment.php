<?php

declare(strict_types=1);

namespace BadShop\Shipping\Domain;

final class Shipment
{
    public function __construct(public readonly string $orderId)
    {
    }
}
