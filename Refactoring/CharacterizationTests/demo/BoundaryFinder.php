<?php

declare(strict_types=1);

/**
 * Hledání hranic: kde se chování skokově mění.
 *
 * Feathers radí psát charakterizační testy tam, kde jsou větve —
 * jenže u cizího kódu nevíš, kde jsou. Tohle je způsob, jak je najít
 * bez čtení: pusť funkci na rozsahu vstupů a najdi místa, kde se
 * výstup zachová jinak, než by odpovídalo lineárnímu růstu.
 *
 * Nenahrazuje čtení kódu. Ale řekne, kam se podívat nejdřív.
 */
final class BoundaryFinder
{
    /**
     * @param callable(int): int $fn
     * @return list<int> vstupy, kde došlo ke skoku
     */
    public static function find(callable $fn, int $from, int $to): array
    {
        $boundaries = [];
        $previousStep = null;

        for ($i = $from + 1; $i <= $to; ++$i) {
            $step = $fn($i) - $fn($i - 1);

            if ($previousStep !== null && $step !== $previousStep) {
                $boundaries[] = $i;
            }

            $previousStep = $step;
        }

        return $boundaries;
    }
}
