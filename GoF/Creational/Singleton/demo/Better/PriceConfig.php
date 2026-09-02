<?php

declare(strict_types=1);

namespace Better;

/**
 * Táž konfigurace, ale jako OBYČEJNÝ OBJEKT.
 *
 * Žádné `getInstance()`, žádný statický stav. Že bude v aplikaci
 * jen jedna instance, zařídí DI kontejner — a je to jeho práce,
 * ne práce téhle třídy.
 */
final readonly class PriceConfig
{
    public function __construct(
        public int $vatPercent = 21,
        public int $freeShippingFromCents = 150000,
    ) {
    }
}
