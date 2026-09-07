<?php

declare(strict_types=1);

namespace Step0;

/**
 * VÝCHOZÍ STAV: funkce, které nikdo nerozumí.
 *
 * Dělá něco s objednávkou a vrací číslo. Co přesně, se nedá říct
 * bez tužky a papíru — a i pak si nejsi jistý.
 *
 * Testy k ní existují a procházejí. Chování tedy měnit nesmíme;
 * chceme mu jen porozumět.
 */
final class Pricing
{
    /** @param list<array{0: int, 1: int, 2: int}> $d */
    public function calc(array $d, int $t, bool $f): int
    {
        $r = 0;

        foreach ($d as $i) {
            $s = $i[0] * $i[1];

            if ($i[1] >= 100 && $t <= 2) {
                $s = (int) ($s * 0.8);
            } elseif ($i[1] >= 100) {
                $s = (int) ($s * 0.9);
            } elseif ($t <= 2) {
                $s = (int) ($s * 0.95);
            }

            if ($i[2] === 1) {
                $s += 2500;
            }

            $r += $s;
        }

        if ($f && $r < 100000) {
            $r += 9900;
        }

        return $r;
    }
}
