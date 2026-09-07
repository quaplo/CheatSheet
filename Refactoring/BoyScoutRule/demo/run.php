<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka boy scout rule.
 *
 * Martinova formulace mluví o commitu — „check a module in cleaner
 * than when you checked it out". To se dá vzít doslova a udělat
 * z toho kontrolu.
 *
 * Detektor odpadků i vzorky si demo půjčuje od litter-pickupu,
 * protože je to táž věc měřená z druhé strany.
 *
 * Spuštění:  php run.php
 */

$litterDemo = __DIR__ . '/../../LitterPickupRefactoring/demo';

if (!is_file($litterDemo . '/Litter.php')) {
    exit("Nenašel jsem detektor odpadků v " . $litterDemo . "\n");
}

require $litterDemo . '/Litter.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/**
 * Kolik odpadků je v souboru.
 *
 * Čísla 100 a 2 jsou argumenty number_format() — nejsou to odpadky
 * a do součtu nepatří. Rozhodnutí, které nástroj neudělá za tebe.
 */
function litterCount(string $file): int
{
    $found = Litter::in($file);
    $found['magické číslo v těle metody'] = array_diff($found['magické číslo v těle metody'], ['100', '2']);

    return array_sum(array_map('count', $found));
}

/**
 * Samotné pravidlo: odešel jsi ze souboru čistší, než jsi přišel?
 *
 * @return array{cleaner: bool, before: int, after: int}
 */
function boyScoutCheck(string $before, string $after): array
{
    $b = litterCount($before);
    $a = litterCount($after);

    return ['cleaner' => $a < $b, 'before' => $b, 'after' => $a];
}

echo "=== Boy scout rule ===\n\n";

echo "    „Always check a module in cleaner than when you checked it out.\"\n";
echo "                       — Robert C. Martin, 2010\n\n";

echo "    Ta věta mluví o commitu. Tím pádem se z ní dá udělat\n";
echo "    kontrola, kterou lze pustit — a to je na její formulaci\n";
echo "    to podstatné.\n\n";

// --- Kontrola nad čtyřmi cestami z litter-pickupu -------------------------

echo "1. Táž oprava chyby, čtyři různé commity\n\n";

$before = $litterDemo . '/Step0/Shipping.php';

$commits = [
    'C: jen oprava chyby'      => $litterDemo . '/PathC/Shipping.php',
    'A: oprava i úklid naráz'  => $litterDemo . '/PathA/Shipping.php',
    'B1: jen úklid'            => $litterDemo . '/PathB1/Shipping.php',
    'B2: úklid, pak oprava'    => $litterDemo . '/PathB2/Shipping.php',
];

printf(
    "    %s%s%s%s\n",
    pad('commit', 28),
    pad('odpadků před', 15),
    pad('po', 6),
    'splňuje pravidlo?',
);

foreach ($commits as $label => $after) {
    $result = boyScoutCheck($before, $after);

    printf(
        "    %s%s%s%s\n",
        pad($label, 28),
        pad((string) $result['before'], 15),
        pad((string) $result['after'], 6),
        $result['cleaner'] ? 'ano' : 'NE — odešel jsi stejně špinavý',
    );
}

echo "\n    Cesta C opravila chybu a nic víc. Funguje, prošla by\n";
echo "    review a pravidlo nesplňuje — protože příští člověk\n";
echo "    najde ten soubor přesně takový, jaký jsi ho našel ty.\n\n";

// --- Co pravidlo neříká ----------------------------------------------------

echo "2. Co ta kontrola neumí\n\n";

$strict = boyScoutCheck($before, $litterDemo . '/PathB1/Shipping.php');

printf("    %s%s\n", pad('umí říct', 26), 'jestli odpadků ubylo');
printf("    %s%s\n", pad('neumí říct', 26), 'jestli je kód po tom lepší');
printf("    %s%s\n", pad('neumí říct', 26), 'jestli se nezměnilo chování');
printf("    %s%s\n\n", pad('neumí říct', 26), 'jestli úklid patřil do tohohle commitu');

printf(
    "    Čistě podle čísel je nejlepší commit ten, který uklidil\n    z %d na %d a nic jiného neudělal. Jenže sám o sobě neopravil\n    nic, kvůli čemu jsi přišel.\n\n",
    $strict['before'],
    $strict['after'],
);

echo "    Pravidlo je návyk, ne metrika. Číslo je jen způsob, jak si\n";
echo "    ho jednou za čas ověřit — ne cíl, na který se optimalizuje.\n";
