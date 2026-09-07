<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka comprehension refactoringu.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Metrics.php';
require __DIR__ . '/Step0/Pricing.php';
require __DIR__ . '/Step1/Pricing.php';
require __DIR__ . '/Step2/Pricing.php';
require __DIR__ . '/Step3/Pricing.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/** Sada vstupů, na které se ověřuje, že se chování nezměnilo. */
function cases(): array
{
    $out = [];

    foreach ([[[12900, 1, 0]], [[12900, 100, 0]], [[4900, 250, 1], [12900, 3, 0]], [[99900, 1, 1]]] as $items) {
        foreach ([1, 2, 3, 5] as $tier) {
            foreach ([true, false] as $shipping) {
                $out[] = [$items, $tier, $shipping];
            }
        }
    }

    return $out;
}

echo "=== Comprehension refactoring ===\n\n";

echo "    Ward Cunningham: „By refactoring I move the understanding\n";
echo "    from my head into the code itself.\"\n\n";

// --- 1. Výchozí stav -------------------------------------------------------

echo "1. Kód, kterému nikdo nerozumí\n\n";

echo "        public function calc(array \$d, int \$t, bool \$f): int\n";
echo "        {\n";
echo "            \$r = 0;\n";
echo "            foreach (\$d as \$i) {\n";
echo "                \$s = \$i[0] * \$i[1];\n";
echo "                if (\$i[1] >= 100 && \$t <= 2) { \$s = (int) (\$s * 0.8); }\n";
echo "                …\n";
echo "        }\n\n";

echo "    Testy k tomu existují a procházejí. Chování tedy měnit\n";
echo "    nesmíme — chceme mu jen porozumět.\n\n";

// --- 2. Tři kroky ----------------------------------------------------------

echo "2. Tři kroky, ani jeden nemění chování\n\n";

$steps = [
    '0. výchozí stav'          => __DIR__ . '/Step0/Pricing.php',
    '1. pojmenované proměnné'  => __DIR__ . '/Step1/Pricing.php',
    '2. pojmenované podmínky'  => __DIR__ . '/Step2/Pricing.php',
    '3. rozdělené na věty'     => __DIR__ . '/Step3/Pricing.php',
];

printf(
    "    %s%s%s%s%s\n",
    pad('krok', 26),
    pad('složitost', 12),
    pad('zanoření', 12),
    pad('magická čísla', 16),
    'pojmenované pojmy',
);

foreach ($steps as $label => $file) {
    $m = Metrics::of($file);

    printf(
        "    %s%s%s%s%d\n",
        pad($label, 26),
        pad((string) $m['complexity'], 12),
        pad((string) $m['depth'], 12),
        pad((string) $m['magicNumbers'], 16),
        $m['namedConcepts'],
    );
}

echo "\n    Všimni si prvního kroku: nezměnil ANI JEDNU metriku.\n";
echo "    Jen se přejmenovaly proměnné — a přesto je ten kód o kus\n";
echo "    srozumitelnější. Metriky pochopení neměří.\n\n";

echo "    Co měří: v kroku 2 zmizela magická čísla (8 → 3) a přibyly\n";
echo "    pojmy (0 → 5). Složitost klesla až v kroku 3, kdy se metoda\n";
echo "    rozdělila — 9 → 4 a zanoření 3 → 2.\n\n";

// --- 3. Chování je totožné -------------------------------------------------

echo "3. Ověření, že se nic nezměnilo\n\n";

$v0 = new Step0\Pricing();
$v1 = new Step1\Pricing();
$v2 = new Step2\Pricing();
$v3 = new Step3\Pricing();

$same = 0;
$diff = [];

foreach (cases() as [$items, $tier, $shipping]) {
    $expected = $v0->calc($items, $tier, $shipping);

    $all = [
        $v1->calc($items, $tier, $shipping),
        $v2->calc($items, $tier, $shipping),
        $v3->calc($items, $tier, $shipping),
    ];

    if (count(array_unique([$expected, ...$all])) === 1) {
        ++$same;
    } else {
        $diff[] = sprintf('tier %d, doprava %s', $tier, $shipping ? 'ano' : 'ne');
    }
}

printf("    %s%d\n", pad('případů', 26), count(cases()));
printf("    %s%d\n", pad('shodných ve všech krocích', 26), $same);
printf("    %s%d\n\n", pad('rozdílů', 26), count($diff));

// --- 4. Co se přitom objevilo ---------------------------------------------

echo "4. Co se při tom objevilo\n\n";

$discovered = [
    'sto kusů a víc'              => 'velkoobchodní množství',
    'zákaznická úroveň 1 a 2'     => 'prémiový zákazník',
    'obojí zároveň'               => 'vlastní, vyšší sleva (20 % místo 10 %)',
    'příplatek 25 Kč'             => 'dárkové balení',
    'doprava zdarma od 1 000 Kč'  => 'pravidlo, které nikde nebylo napsané',
];

printf("    %s%s\n", pad('bylo v kódu jako', 30), 've skutečnosti znamená');

foreach ($discovered as $was => $means) {
    printf("    %s%s\n", pad($was, 30), $means);
}

echo "\n    Tahle pravidla nikdo z nás nevymyslel. Byla tam celou dobu,\n";
echo "    jen nebyla vidět. Refaktoring je nepřidal — pojmenoval je.\n\n";

// --- 5. Kde ta znalost teď je ----------------------------------------------

echo "5. Kde ta znalost skončila\n\n";

printf("    %s%s%s\n", pad('', 30), pad('bez refaktoringu', 22), 'po refaktoringu');
printf("    %s%s%s\n", pad('kde je pochopení', 30), pad('v hlavě', 22), 'v kódu');
printf("    %s%s%s\n", pad('jak dlouho vydrží', 30), pad('týdny', 22), 'dokud kód existuje');
printf("    %s%s%s\n", pad('kdo se k němu dostane', 30), pad('kdo se zeptá', 22), 'kdokoli');
printf("    %s%s%s\n\n", pad('co když odejdu', 30), pad('začíná se znovu', 22), 'zůstává');

echo "    Ralph Johnson popsal tenhle druh refaktoringu jako\n";
echo "    „wiping the dirt off a window so you can see beyond\" —\n";
echo "    okno se nemění, jen je přes něj konečně vidět.\n";
