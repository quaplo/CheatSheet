<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka k dokumentu Out of the Tar Pit.
 *
 * Ukazuje na běhu to, co článek tvrdí textem: stav v kombinaci
 * s pořadím operací vyrábí prostor, který testy nemůžou projít.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/MutableCart.php';
require __DIR__ . '/ImmutableCart.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function orderings(int $n): string
{
    return match (true) {
        $n === 1 => '1 pořadí',
        $n < 5 => $n . ' pořadí',
        default => $n . ' pořadí',
    };
}

/** @return list<list<string>> všechna pořadí daných operací */
function permutations(array $items): array
{
    if (count($items) <= 1) {
        return [$items];
    }

    $result = [];

    foreach ($items as $i => $item) {
        $rest = $items;
        unset($rest[$i]);

        foreach (permutations(array_values($rest)) as $tail) {
            $result[] = [$item, ...$tail];
        }
    }

    return $result;
}

$operations = ['položka 129 Kč', 'položka 49 Kč', 'kupon 10 %', 'doprava 99 Kč'];

echo "=== Out of the Tar Pit: stav a pořadí ===\n\n";

echo "    Čtyři operace nad košíkem. Každá je sama o sobě správně.\n";
echo "    Otázka zní, kolik různých výsledků z nich jde dostat.\n\n";

// --- 1. Měnitelný košík ---------------------------------------------------

echo "1. Košík, který si drží mezivýsledek\n\n";

$mutableResults = [];

foreach (permutations($operations) as $ordering) {
    $cart = new MutableCart();

    foreach ($ordering as $operation) {
        match ($operation) {
            'položka 129 Kč' => $cart->addItem(12900),
            'položka 49 Kč' => $cart->addItem(4900),
            'kupon 10 %' => $cart->applyCoupon(10),
            'doprava 99 Kč' => $cart->addShipping(9900),
        };
    }

    $mutableResults[$cart->total()][] = implode(' → ', $ordering);
}

ksort($mutableResults);

printf("    %s%s\n", pad('částka', 14), 'kolik pořadí k ní vede');

foreach ($mutableResults as $total => $paths) {
    printf("    %s%s\n", pad(number_format($total / 100, 2, ',', ' ') . ' Kč', 14), orderings(count($paths)));
}

echo "\n    Příklady dvou pořadí, která dávají jinou částku:\n\n";

$first = array_key_first($mutableResults);
$last = array_key_last($mutableResults);
printf("      %s\n        → %s\n", $mutableResults[$first][0], number_format($first / 100, 2, ',', ' ') . ' Kč');
printf("      %s\n        → %s\n\n", $mutableResults[$last][0], number_format($last / 100, 2, ',', ' ') . ' Kč');

// --- 2. Neměnný košík -----------------------------------------------------

echo "2. Košík, který počítá až na konci\n\n";

$immutableResults = [];

foreach (permutations($operations) as $ordering) {
    $cart = ImmutableCart::empty();

    foreach ($ordering as $operation) {
        $cart = match ($operation) {
            'položka 129 Kč' => $cart->withItem(12900),
            'položka 49 Kč' => $cart->withItem(4900),
            'kupon 10 %' => $cart->withCoupon(10),
            'doprava 99 Kč' => $cart->withShipping(9900),
        };
    }

    $immutableResults[$cart->total()][] = $ordering;
}

printf("    %s%s\n", pad('částka', 14), 'kolik pořadí k ní vede');

foreach ($immutableResults as $total => $paths) {
    printf("    %s%s\n", pad(number_format($total / 100, 2, ',', ' ') . ' Kč', 14), orderings(count($paths)));
}

echo "\n";

// --- 3. Co z toho plyne pro testy -----------------------------------------

echo "3. Co to znamená pro testy\n\n";

$total = count(permutations($operations));
$correct = array_key_first($immutableResults);   // jediná částka, kterou vrací neměnná verze
$matching = count($mutableResults[$correct] ?? []);

printf("    %s%s%s\n", pad('', 34), pad('měnitelný', 20), 'neměnný');
printf("    %s%s%d\n", pad('možných pořadí', 34), pad((string) $total, 20), $total);
printf("    %s%s%d\n", pad('různých výsledků', 34), pad((string) count($mutableResults), 20), count($immutableResults));
printf(
    "    %s%s%s\n\n",
    pad('pořadí se správnou částkou', 34),
    pad($matching . ' z ' . $total, 20),
    $total . ' z ' . $total,
);

printf(
    "    Správná částka je %s — ta, kterou vrátí verze bez stavu.\n",
    number_format($correct / 100, 2, ',', ' ') . ' Kč',
);
printf(
    "    U měnitelného košíku k ní vede %d z %d pořadí. Kdybys napsal\n    jeden test a netrefil se, projde ti chyba do produkce —\n    a šance, že se netrefíš, je %d %%.\n\n",
    $matching,
    $total,
    (int) round(($total - $matching) / $total * 100),
);

echo "    U neměnného košíku stačí otestovat jedno pořadí a platí to\n";
echo "    pro všech " . $total . ". Není totiž co netrefit.\n\n";

// --- 4. A jak to roste ----------------------------------------------------

echo "4. A takhle to roste\n\n";

printf("    %s%s\n", pad('operací', 12), 'možných pořadí');

$factorial = 1;

foreach (range(1, 8) as $n) {
    $factorial *= $n;
    printf("    %s%s\n", pad((string) $n, 12), number_format($factorial, 0, ',', ' '));
}

echo "\n    Osm operací nad jedním objektem je 40 320 pořadí. Testy jich\n";
echo "    projdou hrst — a článek z toho vyvozuje, že testování je\n";
echo "    vzorkování, ne důkaz.\n\n";

echo "    „…even though the number of possible inputs may be very large,\n";
echo "     the number of possible states the system can be in is often\n";
echo "     EVEN LARGER.\"\n";
echo "                    — Moseley, Marks: Out of the Tar Pit, 2006\n";
