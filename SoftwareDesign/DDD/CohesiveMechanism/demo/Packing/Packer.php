<?php

declare(strict_types=1);

namespace Packing;

use BoxSize;
use PackableItem;

/**
 * SOUDRŽNÝ MECHANISMUS — rozhraní odhalující záměr.
 *
 * Doména řekne „zabal tyhle položky do těchhle krabic" a dostane plán.
 * O tom, že uvnitř běží heuristika bin packingu, nemusí vědět nic.
 *
 * Klíčové je, co tady NENÍ: žádná znalost objednávky, zákazníka,
 * dopravce ani ceny zboží. Mechanismus zná jen objemy a kapacity.
 */
interface Packer
{
    /**
     * @param list<PackableItem> $items
     * @param list<BoxSize> $availableBoxes
     */
    public function pack(array $items, array $availableBoxes): PackingPlan;
}
