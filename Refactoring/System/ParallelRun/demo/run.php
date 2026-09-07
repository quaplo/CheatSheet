<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka techniky Parallel Run.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Order.php';
require __DIR__ . '/DiscountCalculator.php';
require __DIR__ . '/LegacyDiscount.php';
require __DIR__ . '/NewDiscount.php';
require __DIR__ . '/BrokenDiscount.php';
require __DIR__ . '/Observation.php';
require __DIR__ . '/Experiment.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function czk(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}

/** @return list<Order> */
function orders(int $count = 500): array
{
    $tiers = ['standard', 'standard', 'standard', 'vip'];
    $out = [];

    for ($i = 0; $i < $count; ++$i) {
        $out[] = new Order(
            number: sprintf('2026/%04d', $i),
            totalInCents: 30000 + ($i * 7331) % 900000,
            itemCount: 1 + $i % 9,
            customerTier: $tiers[$i % 4],
            hasCoupon: $i % 5 === 0,
        );
    }

    return $out;
}

echo "=== Parallel Run ===\n\n";

// --- 1. Vrací se vždycky kontrola -----------------------------------------

echo "1. Kandidát nikdy neovlivní odpověď\n\n";

$control = new LegacyDiscount();
$candidate = new NewDiscount();
$experiment = new Experiment($control, $candidate);

$order = new Order('2026/0001', 320000, 3, 'vip', false);

printf("    %s%s\n", pad('kontrola sama', 26), czk($control->discountInCents($order)));
printf("    %s%s\n", pad('kandidát sám', 26), czk($candidate->discountInCents($order)));
printf("    %s%s   ← co dostane zákazník\n\n", pad('experiment vrátil', 26), czk($experiment->run($order)));

echo "    Odpověď je z kontroly. Kandidát běžel, ale jeho výsledek\n";
echo "    se jen zaznamenal — do produkce se nedostal.\n\n";

// --- 2. Co se rozchází ----------------------------------------------------

echo "2. Kde se implementace rozcházejí\n\n";

$experiment = new Experiment($control, $candidate);

foreach (orders() as $o) {
    $experiment->run($o);
}

$mismatchRate = count($experiment->mismatches) / $experiment->candidateRuns * 100;

printf("    %s%d\n", pad('objednávek', 26), $experiment->runs);
printf("    %s%d (%.1f %%)\n\n", pad('neshod', 26), count($experiment->mismatches), $mismatchRate);

printf("    %s%s%s%s\n", pad('objednávka', 16), pad('kontrola', 16), pad('kandidát', 16), 'rozdíl');

foreach (array_slice($experiment->mismatches, 0, 5) as $m) {
    printf(
        "    %s%s%s%s\n",
        pad($m['order']->number, 16),
        pad($m['control']->describe(), 16),
        pad($m['candidate']->describe(), 16),
        czk(abs($m['control']->value - $m['candidate']->value)),
    );
}

printf("\n    (zobrazeno prvních 5 z %d)\n\n", count($experiment->mismatches));

// Největší rozdíly — tam, kde nejde o zaokrouhlení.
$byDiff = $experiment->mismatches;
usort($byDiff, static fn (array $a, array $b): int
    => abs($b['control']->value - $b['candidate']->value) <=> abs($a['control']->value - $a['candidate']->value));

echo "    největší rozdíly:\n";

foreach (array_slice($byDiff, 0, 3) as $m) {
    printf(
        "    %s%s%s%s\n",
        pad($m['order']->number, 16),
        pad($m['control']->describe(), 16),
        pad($m['candidate']->describe(), 16),
        czk(abs($m['control']->value - $m['candidate']->value)),
    );
}

echo "\n";

// --- 3. Ne každá neshoda je chyba -----------------------------------------

echo "3. Ne každý rozdíl je chyba\n\n";

$tolerant = new Experiment($control, $candidate, toleranceInCents: 100);

foreach (orders() as $o) {
    $tolerant->run($o);
}

printf("    %s%d\n", pad('bez tolerance', 26), count($experiment->mismatches));
printf("    %s%d\n\n", pad('s tolerancí 1 Kč', 26), count($tolerant->mismatches));

$real = count($tolerant->mismatches);

printf(
    "    zbyl%s %d skutečn%s rozdíl%s z %d\n\n",
    $real === 1 ? '' : 'y',
    $real,
    $real === 1 ? 'ý' : 'é',
    $real === 1 ? '' : 'y',
    count($experiment->mismatches),
);

foreach ($tolerant->mismatches as $m) {
    printf(
        "    %s%s%s%s\n",
        pad($m['order']->number, 16),
        pad($m['control']->describe(), 16),
        pad($m['candidate']->describe(), 16),
        czk(abs($m['control']->value - $m['candidate']->value)),
    );
}

echo "\n    Většina neshod je haléřové zaokrouhlení — nová verze\n";
echo "    zaokrouhluje dolů. To je rozhodnutí, ne chyba.\n\n";

echo "    Zbývá jediný případ z pěti set: malá objednávka s kupónem,\n";
echo "    kde se strop uplatní jinak. Právě takové případy jsou důvod,\n";
echo "    proč technika existuje — v jednotkových testech na něj nikdo\n";
echo "    nepomyslel, protože o tom pravidle nikdo nevěděl.\n\n";

// --- 4. Výjimka v kandidátovi nesmí nic shodit ---------------------------

echo "4. Kandidát spadne — a systém běží dál\n\n";

$risky = new Experiment($control, new BrokenDiscount());
$served = 0;
$crashed = 0;

foreach (orders(20) as $i => $o) {
    // Každá pátá objednávka bude mít nula položek — kandidát na ní spadne.
    $o = $i % 5 === 0
        ? new Order($o->number, $o->totalInCents, 0, $o->customerTier, $o->hasCoupon)
        : $o;

    try {
        $risky->run($o);
        ++$served;
    } catch (Throwable) {
        ++$crashed;
    }
}

printf("    %s%d\n", pad('objednávek', 26), 20);
printf("    %s%d\n", pad('obslouženo', 26), $served);
printf("    %s%d\n", pad('spadlo zákazníkovi', 26), $crashed);
printf("    %s%d\n\n", pad('výjimek v kandidátovi', 26), $risky->candidateErrors);

echo "    Kandidát spadl čtyřikrát a zákazník o tom neví. Tohle je\n";
echo "    vlastnost, kvůli které se technika používá — nový kód se\n";
echo "    zkouší na produkci, aniž by ji mohl rozbít.\n\n";

// --- 5. Co to stojí --------------------------------------------------------

echo "5. Cena: obojí se počítá\n\n";

$measured = new Experiment($control, $candidate);

foreach (orders(2000) as $o) {
    $measured->run($o);
}

printf("    %s%.2f ms\n", pad('kontrola celkem', 26), $measured->controlTotalMs);
printf("    %s%.2f ms\n", pad('kandidát celkem', 26), $measured->candidateTotalMs);
printf(
    "    %s%.1f× práce navíc\n\n",
    pad('celková režie', 26),
    $measured->candidateTotalMs / $measured->controlTotalMs,
);

echo "    U výpočtu v paměti je to jedno. U volání do databáze nebo\n";
echo "    na cizí službu se tím zdvojnásobí zátěž — a proto se vzorkuje.\n\n";

// --- 6. Vzorkování ---------------------------------------------------------

echo "6. Vzorkování: na kolika procentech provozu\n\n";

printf("    %s%s%s\n", pad('vzorek', 14), pad('kandidát běžel', 20), 'nalezených neshod');

foreach ([1, 10, 50, 100] as $percent) {
    $sampled = new Experiment($control, $candidate, samplePercent: $percent);

    foreach (orders() as $o) {
        $sampled->run($o);
    }

    printf(
        "    %s%s%d\n",
        pad($percent . ' %', 14),
        pad($sampled->candidateRuns . 'x', 20),
        count($sampled->mismatches),
    );
}

echo "\n    I jednoprocentní vzorek najde, že se implementace liší.\n";
echo "    Na potvrzení, že se NEliší, je ho ale málo — a to je\n";
echo "    ten rozdíl, na kterém technika stojí.\n";
