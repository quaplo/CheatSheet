<?php

declare(strict_types=1);

/**
 * Záměrně neúplná třída — chybí requiresAddress() a label().
 *
 * Tenhle soubor se NEdá spustit a to je jeho účel: ukázat, že chybu
 * zachytí PHP samo. Demo ho pouští v samostatném procesu, protože
 * takovou chybu nelze odchytit uvnitř běžícího skriptu.
 */

require __DIR__ . '/../Shipment.php';
require __DIR__ . '/../After/ShippingMethod.php';

final class Nekompletni extends After\ShippingMethod
{
    public function code(): string
    {
        return 'x';
    }

    public function priceInCents(Shipment $shipment): int
    {
        return 0;
    }

    public function deliveryDays(): int
    {
        return 1;
    }

    // requiresAddress() a label() schválně chybí
}

echo "tenhle řádek se nikdy nevypíše\n";
