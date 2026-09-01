<?php

declare(strict_types=1);

/**
 * Strategie: jeden způsob výpočtu ceny dopravy.
 *
 * Tohle je to rozhraní, kvůli kterému celý pattern existuje — kontext
 * (ShippingCalculator) zná jen tenhle kontrakt, ne konkrétní dopravce.
 */
interface ShippingCost
{
    /** Strojový kód dopravy, pod kterým si ji zákazník vybere. */
    public function code(): string;

    /** Cena dopravy v haléřích. */
    public function calculate(Order $order): int;
}
