<?php

declare(strict_types=1);

/**
 * Volající kód — jediné místo, které dopravu používá.
 *
 * KROK 2 spočíval v tom, že přestal volat LegacyTableCalculator
 * napřímo a začal si nechat předat ShippingCalculator.
 *
 * Od té chvíle se pod ním dá vyměnit cokoli, aniž by se ho to dotklo.
 * Všimni si, že v celém souboru není ani jedno jméno konkrétní
 * implementace.
 */
final class CheckoutService
{
    public function __construct(private readonly ShippingCalculator $shipping)
    {
    }

    public function summarise(Shipment $shipment): string
    {
        $quote = $this->shipping->quoteFor($shipment);

        return sprintf('Doprava: %s', $quote->format());
    }
}
