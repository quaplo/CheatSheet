<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka refaktoringu Replace Primitive with Object.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Before/Validation.php';
require __DIR__ . '/Before/Cart.php';
require __DIR__ . '/After/Sku.php';
require __DIR__ . '/After/Cart.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function yesNo(bool $value): string
{
    return $value ? 'ano' : 'ne';
}

echo "=== Replace Primitive with Object ===\n\n";

// --- 1. Tři místa, tři pravidla -------------------------------------------

echo "1. Totéž SKU se validuje třikrát — a pokaždé jinak\n\n";

$inputs = [
    'MON-27',
    'mon-27',
    'MON-270',
    'MONITOR-27',
    'MON 27',
    '  MON-27  ',
    'X-1',
];

printf("    %s%s%s%s%s\n", pad('vstup', 16), pad('formulář', 12), pad('import', 12), pad('API', 12), 'shoda');

$disagreements = 0;

foreach ($inputs as $input) {
    $form = Before\Validation::inAdminForm($input);
    $import = Before\Validation::inImport($input);
    $api = Before\Validation::inApi($input);

    $agree = ($form === $import) && ($import === $api);

    if (!$agree) {
        ++$disagreements;
    }

    printf(
        "    %s%s%s%s%s\n",
        pad('„' . $input . '"', 16),
        pad(yesNo($form), 12),
        pad(yesNo($import), 12),
        pad(yesNo($api), 12),
        $agree ? 'ano' : 'NE',
    );
}

printf("\n    vstupů:                %d\n", count($inputs));
printf("    neshod mezi místy:     %d\n\n", $disagreements);

echo "    Záleží na tom, kudy hodnota do systému přišla. Co projde\n";
echo "    přes formulář, spadne v importu — a nikdo neví, které\n";
echo "    z těch tří pravidel je to správné.\n\n";

// --- 2. Chybějící normalizace ----------------------------------------------

echo "2. Bez normalizace jsou „MON-27\" a „mon-27\" dvě věci\n\n";

$oldCart = new Before\Cart();
$oldCart->add('MON-27', 1);
$oldCart->add('mon-27', 1);
$oldCart->add(' MON-27 ', 1);

printf("    přidáno třikrát totéž zboží\n");
printf("    %s%d\n\n", pad('různých položek v košíku', 30), $oldCart->distinctItems());

foreach ($oldCart->lines() as $sku => $qty) {
    printf("        „%s\" → %d ks\n", $sku, $qty);
}

echo "\n    Zákazník má v košíku třikrát tentýž monitor a myslí si,\n";
echo "    že jsou to tři různé věci.\n\n";

// --- 3. Po refaktoringu: jedno pravidlo ------------------------------------

echo "3. Po refaktoringu: jedna definice platnosti\n\n";

printf("    %s%s%s\n", pad('vstup', 16), pad('platné?', 12), 'výsledek');

foreach ($inputs as $input) {
    $valid = After\Sku::isValid($input);

    printf(
        "    %s%s%s\n",
        pad('„' . $input . '"', 16),
        pad(yesNo($valid), 12),
        $valid ? (string) After\Sku::fromString($input) : '—',
    );
}

echo "\n    Jedno pravidlo pro formulář, import i API. A normalizace\n";
echo "    je jeho součástí — „  mon-27  \" se stane „MON-27\".\n\n";

// --- 4. Košík po refaktoringu ---------------------------------------------

echo "4. Košík, který pozná totéž zboží\n\n";

$newCart = new After\Cart();
$newCart->add(After\Sku::fromString('MON-27'), 1);
$newCart->add(After\Sku::fromString('mon-27'), 1);
$newCart->add(After\Sku::fromString(' MON-27 '), 1);

printf("    přidáno třikrát totéž zboží\n");
printf("    %s%d\n\n", pad('různých položek v košíku', 30), $newCart->distinctItems());

foreach ($newCart->lines() as $line) {
    printf("        %s → %d ks\n", $line['sku'], $line['quantity']);
}

echo "\n";

// --- 5. Neplatná hodnota nevznikne ----------------------------------------

echo "5. Neplatné SKU nevznikne\n\n";

try {
    After\Sku::fromString('MONITOR-27');
    echo "    prošlo — CHYBA\n\n";
} catch (InvalidArgumentException $e) {
    printf("    %s\n\n", $e->getMessage());
}

echo "    Tohle je ten hlavní rozdíl. U řetězce se musí kontrolovat\n";
echo "    všude, kde se použije. U typu stačí jednou — při vzniku.\n\n";

// --- 6. Chování, které řetězec neměl --------------------------------------

echo "6. Objekt může umět víc než řetězec\n\n";

$skus = array_map(
    static fn (string $s): After\Sku => After\Sku::fromString($s),
    ['MON-27', 'MON-32', 'KLA-01'],
);

printf("    %s%s\n", pad('SKU', 14), 'skupina produktu');

foreach ($skus as $sku) {
    printf("    %s%s\n", pad((string) $sku, 14), $sku->productGroup());
}

printf(
    "\n    MON-27 a MON-32 ze stejné skupiny:  %s\n",
    yesNo($skus[0]->productGroup() === $skus[1]->productGroup()),
);
printf(
    "    MON-27 a KLA-01 ze stejné skupiny:  %s\n\n",
    yesNo($skus[0]->productGroup() === $skus[2]->productGroup()),
);

echo "    productGroup() je znalost, která se dřív musela odvozovat\n";
echo "    přes substr() všude, kde byla potřeba — nebo se neodvozovala\n";
echo "    a nikdo o ní nevěděl.\n";
