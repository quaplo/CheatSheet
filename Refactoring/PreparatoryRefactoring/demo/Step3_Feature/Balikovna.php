<?php

declare(strict_types=1);

namespace Step3_Feature;

use Step2_Prepared\FreeOverThreshold;
use Step2_Prepared\ShippingMethod;

/**
 * PŘIDÁNÍ FUNKCE do připraveného kódu.
 *
 * Celá změna je tenhle jeden soubor. Do ničeho existujícího se
 * nesahalo — pravidlo o dopravě zdarma se převzalo, ne zopakovalo.
 *
 * „…then make the easy change."
 */
final class Balikovna implements ShippingMethod
{
    use FreeOverThreshold;

    public function code(): string
    {
        return 'balikovna';
    }

    public function priceInCents(int $weightInGrams, int $orderValueInCents): int
    {
        if ($this->isFree($orderValueInCents)) {
            return 0;
        }

        return $weightInGrams > 10000 ? 12900 : 6900;
    }
}
