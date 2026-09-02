<?php

declare(strict_types=1);

namespace Vendor;

/**
 * CIZÍ knihovna dopravce č. 1. Nezměníme ji.
 *
 * Nesedí nám na ní skoro nic:
 *   · jiné pojmenování metody i parametrů
 *   · hmotnost v kilogramech, ne v gramech
 *   · cena jako float v korunách
 *   · dodací lhůta jako text („2-3 dny“)
 *   · vrací asociativní pole, ne objekt
 */
final class BalikovnaApi
{
    /** @return array{cena: float, lhuta: string, sluzba: string} */
    public function spocitejCenu(string $zeme, float $kilogramy): array
    {
        $cena = 69.0 + max(0.0, $kilogramy - 5.0) * 12.5;

        if ($zeme !== 'CZ') {
            $cena *= 2;
        }

        return ['cena' => round($cena, 2), 'lhuta' => '2-3 dny', 'sluzba' => 'Balíkovna'];
    }
}
