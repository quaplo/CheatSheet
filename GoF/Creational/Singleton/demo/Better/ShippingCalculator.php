<?php

declare(strict_types=1);

namespace Better;

/**
 * Táž kalkulačka, ale závislost je vidět v konstruktoru.
 *
 * Rozdíl proti singletonové verzi:
 *   · z podpisu je jasné, co třída potřebuje
 *   · v testu podstrčíš jinou konfiguraci bez globálního stavu
 *   · dvě různé konfigurace můžou existovat vedle sebe
 *
 * A pořád platí, že v běžící aplikaci bude instance jen jedna —
 * jen o tom rozhoduje kontejner, ne třída sama.
 */
final readonly class ShippingCalculator
{
    public function __construct(
        private PriceConfig $config,
    ) {
    }

    public function calculate(int $orderTotalInCents): int
    {
        return $orderTotalInCents >= $this->config->freeShippingFromCents ? 0 : 9900;
    }
}
