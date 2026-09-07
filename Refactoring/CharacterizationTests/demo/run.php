<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka charakterizačních testů.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/LegacyPricing.php';
require __DIR__ . '/TinyTest.php';
require __DIR__ . '/BoundaryFinder.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function czk(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}

$pricing = new LegacyPricing();
$test = new TinyTest();

echo "=== Charakterizační testy ===\n\n";

// --- 1. Test, který má selhat ---------------------------------------------

echo "1. Napiš test, o kterém víš, že selže\n\n";

echo "    \$this->assertSame(0, \$pricing->finalPriceInCents(10000, 5, 'standard'));\n\n";

$test->assertSame(0, $pricing->finalPriceInCents(10000, 5, 'standard'), 'pět kusů, standardní zákazník');

foreach ($test->failures() as $failure) {
    printf("    %s\n", $failure);
}

echo "\n    Selhání právě prozradilo, co ten kód dělá. Teď se skutečná\n";
echo "    hodnota zapíše do testu — a máme první charakterizační test.\n\n";

echo "    Proč zrovna nula? Feathers to doporučuje schválně:\n";
echo "    záměrně selhávající tvrzení je falzifikovatelný pokus.\n";
echo "    Kdyby neselhalo, je test tautologický a nic neověřuje.\n\n";

// --- 2. Kde jsou hranice ---------------------------------------------------

echo "2. Kde vůbec psát testy? Najdi hranice\n\n";

$boundaries = BoundaryFinder::find(
    static fn (int $q): int => $pricing->finalPriceInCents(10000, $q, 'standard'),
    from: 1,
    to: 60,
);

printf("    prohledáno množství:   1–60\n");
printf("    nalezené skoky:        %s\n\n", implode(', ', $boundaries));

printf("    %s%s%s\n", pad('množství', 14), pad('cena', 18), 'rozdíl proti předchozímu');

foreach ([9, 10, 11, 12, 13, 14, 49, 50] as $q) {
    $current = $pricing->finalPriceInCents(10000, $q, 'standard');
    $previous = $pricing->finalPriceInCents(10000, $q - 1, 'standard');

    printf(
        "    %s%s%s\n",
        pad((string) $q, 14),
        pad(czk($current), 18),
        czk($current - $previous),
    );
}

echo "\n    Tři skoky: u 10 a 50 jsou to množstevní slevy. A u 13\n";
echo "    je něco, co nikdo nečekal.\n\n";

// --- 3. Test zapíše i to, co vypadá jako chyba ----------------------------

echo "3. Test zapíše i to, co vypadá jako chyba\n\n";

$at12 = $pricing->finalPriceInCents(10000, 12, 'standard');
$at13 = $pricing->finalPriceInCents(10000, 13, 'standard');
$at14 = $pricing->finalPriceInCents(10000, 14, 'standard');

printf("    %s%s\n", pad('12 kusů', 14), czk($at12));
printf("    %s%s   ← o korunu víc, než by mělo\n", pad('13 kusů', 14), czk($at13));
printf("    %s%s\n\n", pad('14 kusů', 14), czk($at14));

echo "    U třinácti kusů se připočítá 100 haléřů. Vypadá to jako\n";
echo "    chyba a nejspíš to chyba je. Charakterizační test ji přesto\n";
echo "    zapíše tak, jak je:\n\n";

echo "        \$this->assertSame(" . $at13 . ", \$pricing->finalPriceInCents(10000, 13, 'standard'));\n\n";

echo "    Není to schvalování chyby. Je to konstatování, že tohle kód\n";
echo "    dnes dělá — a že to při refaktoringu nesmí zmizet nechtěně.\n";
echo "    Opravit se to má samostatně, s vědomím, že se mění chování.\n\n";

// --- 4. Sada testů ---------------------------------------------------------

echo "4. Sada, která popisuje současné chování\n\n";

/** Sada, jaká vznikne, když se testuje „jedna cena a různá množství". */
function narrowCases(LegacyPricing $pricing): array
{
    $cases = [];

    foreach ([1, 9, 10, 13, 49, 50, 100] as $quantity) {
        foreach (['standard', 'vip', 'partner'] as $type) {
            $cases[] = [10000, $quantity, $type, $pricing->finalPriceInCents(10000, $quantity, $type)];
        }
    }

    return $cases;
}

/** Sada, která mění i základní cenu — a tím i zaokrouhlování. */
function wideCases(LegacyPricing $pricing): array
{
    $cases = [];

    foreach ([333, 999, 1234, 4999, 7777, 10000, 12345] as $base) {
        foreach ([1, 7, 10, 13, 25, 50, 77, 100] as $quantity) {
            foreach (['standard', 'vip', 'partner'] as $type) {
                $cases[] = [$base, $quantity, $type, $pricing->finalPriceInCents($base, $quantity, $type)];
            }
        }
    }

    return $cases;
}

$narrow = narrowCases($pricing);
$wide = wideCases($pricing);

printf("    %s%d\n", pad('úzká sada (jedna cena)', 30), count($narrow));
printf("    %s%d\n\n", pad('široká sada (sedm cen)', 30), count($wide));

echo "    Obě sady nikde neříkají, co JE správně. Říkají, co kód dělá.\n\n";

// --- 5. Refaktoring, který se povedl --------------------------------------

echo "5. Síť v akci: přepis, který chování zachoval\n\n";

/** Přepis, který zaokrouhluje stejně jako legacy — po každém kroku. */
$faithful = static function (int $base, int $quantity, string $type): int {
    $price = $base * $quantity;

    $price = $quantity >= 10 ? (int) ($price * 0.9) : $price;
    $price = $quantity >= 50 ? (int) ($price * 0.95) : $price;

    $price = match ($type) {
        'vip' => (int) ($price * 0.93),
        'partner' => (int) ($price * 0.85),
        default => $price,
    };

    if ($price > 1000000) {
        $price -= 5000;
    }

    if ($quantity === 13) {
        $price += 100;
    }

    return max($price, 0);
};

$test->reset();

foreach ($wide as [$base, $quantity, $type, $expected]) {
    $test->assertSame($expected, $faithful($base, $quantity, $type), sprintf('%d × %d ks, %s', $base, $quantity, $type));
}

printf("    %s%d z %d\n\n", pad('prošlo', 26), $test->passed(), count($wide));

// --- 6. Refaktoring, který chování změnil ---------------------------------

echo "6. Síť v akci: přepis, který chování změnil\n\n";

/** Vypadá stejně, ale zaokrouhluje jednou na konci místo po každém kroku. */
$broken = static function (int $base, int $quantity, string $type): int {
    $price = $base * $quantity;
    $rate = 1.0;

    if ($quantity >= 10) {
        $rate *= 0.9;
    }

    if ($quantity >= 50) {
        $rate *= 0.95;
    }

    $rate *= match ($type) {
        'vip' => 0.93,
        'partner' => 0.85,
        default => 1.0,
    };

    $price = (int) ($price * $rate);

    if ($price > 1000000) {
        $price -= 5000;
    }

    if ($quantity === 13) {
        $price += 100;
    }

    return max($price, 0);
};

// Nejdřív úzkou sadou.
$test->reset();

foreach ($narrow as [$base, $quantity, $type, $expected]) {
    $test->assertSame($expected, $broken($base, $quantity, $type), sprintf('%d × %d ks, %s', $base, $quantity, $type));
}

$narrowFailures = count($test->failures());

// Teď širokou.
$test->reset();

foreach ($wide as [$base, $quantity, $type, $expected]) {
    $test->assertSame($expected, $broken($base, $quantity, $type), sprintf('%d × %d ks, %s', $base, $quantity, $type));
}

$wideFailures = count($test->failures());

printf("    %s%s%s\n", pad('sada', 26), pad('případů', 12), 'zachyceno rozdílů');
printf("    %s%s%d\n", pad('úzká (jedna cena)', 26), pad((string) count($narrow), 12), $narrowFailures);
printf("    %s%s%d\n\n", pad('široká (sedm cen)', 26), pad((string) count($wide), 12), $wideFailures);

foreach (array_slice($test->failures(), 0, 3) as $failure) {
    printf("        %s\n", $failure);
}

printf("\n    (zobrazeny první 3 z %d)\n\n", $wideFailures);

echo "    Tohle je nejdůležitější řádek celého dema. Rozbitý přepis\n";
echo "    prošel úzkou sadou beze zbytku — a přitom mění ceny.\n\n";

echo "    Rozdíl je v zaokrouhlování: legacy zaokrouhluje po každém\n";
echo "    kroku, přepis až na konci. U ceny 10 000 haléřů to nevyjde\n";
echo "    najevo, protože čísla dělí beze zbytku. U 333 ano.\n\n";

echo "    Charakterizační testy jsou přesně tak dobré jako sada vstupů.\n";
echo "    Zelená sada neznamená, že se chování nezměnilo — znamená,\n";
echo "    že se nezměnilo TAM, KAM SES PODÍVAL.\n";
