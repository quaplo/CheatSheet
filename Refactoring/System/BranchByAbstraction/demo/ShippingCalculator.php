<?php

declare(strict_types=1);

/**
 * KROK 1: ABSTRAKČNÍ VRSTVA.
 *
 * Rozhraní, které stojí mezi voláním a implementací. Vzniká jako první
 * a je jediné, co o výpočtu dopravy zbytek aplikace ví.
 *
 * Fowler: „Create an abstraction layer that encapsulates the interaction
 * between the client code and the current supplier."
 *
 * Podstatné je, že rozhraní se odvozuje ze STARÉ implementace —
 * ne z toho, jak by to mělo vypadat. Kdyby se navrhlo podle nové,
 * první krok by rozbil to, co funguje.
 */
interface ShippingCalculator
{
    public function quoteFor(Shipment $shipment): ShippingQuote;
}
