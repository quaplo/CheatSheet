<?php

declare(strict_types=1);

namespace Domain;

/**
 * Rozhraní, které si definuje NAŠE aplikace.
 *
 * Vzniklo z toho, co potřebujeme — ne z toho, co náhodou nabízí
 * první knihovna, na kterou jsme narazili. To je celý smysl:
 * kontrakt vlastníme my, dopravci se přizpůsobí.
 */
interface ShippingProvider
{
    public function quote(string $countryCode, int $weightInGrams, int $orderValueInCents): ShippingQuote;
}
