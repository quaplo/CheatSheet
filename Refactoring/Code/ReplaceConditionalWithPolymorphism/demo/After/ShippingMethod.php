<?php

declare(strict_types=1);

namespace After;

use Shipment;

/**
 * PO: každý dopravce je vlastní třída.
 *
 * Metody jsou abstraktní schválně. Kdyby měly výchozí implementaci,
 * dala by se nová třída napsat neúplná — a chyba by se ukázala až
 * za běhu. Takhle ji PHP nedovolí ani vytvořit.
 */
abstract class ShippingMethod
{
    abstract public function code(): string;

    abstract public function priceInCents(Shipment $shipment): int;

    abstract public function deliveryDays(): int;

    abstract public function requiresAddress(): bool;

    abstract public function label(): string;
}
