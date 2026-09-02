<?php

declare(strict_types=1);

namespace Classic;

/**
 * Třída, která singleton používá.
 *
 * Podívej se na konstruktor: je prázdný. Z podpisu třídy NIJAK
 * nepoznáš, že potřebuje konfiguraci — závislost je schovaná
 * uprostřed metody.
 *
 * Tomu se říká skrytá závislost a je to hlavní problém singletonu.
 * Ne to, že je jen jedna instance, ale že to na třídě není vidět.
 */
final class ShippingCalculator
{
    public function calculate(int $orderTotalInCents): int
    {
        $config = PriceConfig::getInstance();     // ← závislost, kterou nikdo nevidí

        return $orderTotalInCents >= $config->freeShippingFromCents ? 0 : 9900;
    }
}
