<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka litter-pickup refaktoringu.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Litter.php';
require __DIR__ . '/Step0/Shipping.php';
require __DIR__ . '/PathA/Shipping.php';
require __DIR__ . '/PathB1/Shipping.php';
require __DIR__ . '/PathB2/Shipping.php';
require __DIR__ . '/PathC/Shipping.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function lines(int $n): string
{
    return match (true) {
        $n === 1 => '1 řádek',
        $n < 5 => $n . ' řádky',
        default => $n . ' řádků',
    };
}

/**
 * Odstraní to, co patří k ukázce, ne ke kódu: řádek s namespace
 * a vysvětlující docblock nad třídou. Bez toho by se do každého
 * diffu započítalo lešení téhle ukázky.
 */
function stripped(string $file): string
{
    $code = file_get_contents($file);
    $code = preg_replace('/^namespace .*;\n\n/m', '', $code);
    $code = preg_replace('#^/\*\*.*?\*/\n#ms', '', $code);

    $tmp = tempnam(sys_get_temp_dir(), 'litter') . '.php';
    file_put_contents($tmp, $code);

    return $tmp;
}

/** Velikost diffu mezi dvěma soubory, spočítaná gitem — bez lešení ukázky. */
function diffSize(string $from, string $to): array
{
    $from = stripped($from);
    $to = stripped($to);
    $cmd = sprintf('git diff --no-index --numstat %s %s 2>/dev/null', escapeshellarg($from), escapeshellarg($to));
    $out = trim((string) shell_exec($cmd));

    if ($out === '') {
        return ['added' => 0, 'removed' => 0];
    }

    [$added, $removed] = explode("\t", $out);

    @unlink($from);
    @unlink($to);

    return ['added' => (int) $added, 'removed' => (int) $removed];
}

/** Vstupy pro ověření chování. Hodnota 100000 je přesně ta z hlášení chyby. */
function cases(): array
{
    $out = [];

    foreach ([50000, 99900, 100000, 100100, 250000] as $orderValue) {
        foreach ([500, 10000, 15000] as $weight) {
            foreach (['CZ', 'SK'] as $country) {
                $out[] = [$orderValue, $weight, $country];
            }
        }
    }

    return $out;
}

function behaviourOf(object $shipping): array
{
    $out = [];

    foreach (cases() as [$orderValue, $weight, $country]) {
        $out[] = implode('|', [
            $shipping->priceFor($orderValue, $weight, $country),
            $shipping->label($orderValue, $weight, $country),
            $shipping->receiptLine($orderValue, $weight, $country),
        ]);
    }

    return $out;
}

echo "=== Litter-pickup refactoring ===\n\n";

echo "    Zadání: „Objednávka přesně za 1 000 Kč platí dopravu,\n";
echo "    i když ji má mít zdarma.\"  Oprava je jeden znak: > → >=\n\n";

// --- 1. Co je v tom souboru za nepořádek -----------------------------------

echo "1. Cestou vidíš nepořádek\n\n";

$found = Litter::in(__DIR__ . '/Step0/Shipping.php');

foreach ($found as $kind => $items) {
    printf("    %s%s\n", pad($kind, 32), $items === [] ? '—' : implode(', ', $items));
}

echo "\n    Poslední řádek je past. Čísla 100 a 2 jsou argumenty\n";
echo "    number_format() — nejsou to odpadky. Nástroj najde\n";
echo "    kandidáty, ne odpadky. Rozhodnout musí člověk.\n\n";

// --- 2. Kolik odpadků zbude ------------------------------------------------

echo "2. Kolik odpadků zbude v jednotlivých cestách\n\n";

$variants = [
    'výchozí stav'                  => __DIR__ . '/Step0/Shipping.php',
    'C: jen oprava, bez úklidu'     => __DIR__ . '/PathC/Shipping.php',
    'A: úklid + oprava naráz'       => __DIR__ . '/PathA/Shipping.php',
    'B1: jen úklid'                 => __DIR__ . '/PathB1/Shipping.php',
    'B2: úklid, pak oprava'         => __DIR__ . '/PathB2/Shipping.php',
];

printf("    %s%s\n", pad('varianta', 32), 'skutečné odpadky');

foreach ($variants as $label => $file) {
    $items = Litter::in($file);
    // number_format(…, 100, 2) je legitimní — do součtu nepatří.
    $items['magické číslo v těle metody'] = array_values(
        array_diff($items['magické číslo v těle metody'], ['100', '2']),
    );

    printf("    %s%d\n", pad($label, 32), array_sum(array_map('count', $items)));
}

echo "\n    Cesta C je ta nejčastější a ve Fowlerově postupu chybí:\n";
echo "    „Getting the feature finished is not enough to be done.\"\n\n";

// --- 3. Chování ------------------------------------------------------------

echo "3. Co se změnilo na chování\n\n";

$b = [
    'Step0' => behaviourOf(new Step0\Shipping()),
    'A'     => behaviourOf(new PathA\Shipping()),
    'B1'    => behaviourOf(new PathB1\Shipping()),
    'B2'    => behaviourOf(new PathB2\Shipping()),
    'C'     => behaviourOf(new PathC\Shipping()),
];

$total = count(cases());

function differences(array $left, array $right): int
{
    $n = 0;

    foreach ($left as $i => $value) {
        if ($value !== $right[$i]) {
            ++$n;
        }
    }

    return $n;
}

$comparisons = [
    'výchozí stav  vs  B1 (jen úklid)'   => differences($b['Step0'], $b['B1']),
    'výchozí stav  vs  A (úklid+oprava)' => differences($b['Step0'], $b['A']),
    'A  vs  B2 (stejný cílový stav)'     => differences($b['A'], $b['B2']),
    'C  vs  B2 (uklizeno vs neuklizeno)' => differences($b['C'], $b['B2']),
];

printf("    %s%s\n", pad('porovnání', 40), 'rozdílů z ' . $total);

foreach ($comparisons as $label => $n) {
    printf("    %s%d\n", pad($label, 40), $n);
}

echo "\n    Úklid nezměnil nic. Oprava změnila 6 případů — všechno\n";
echo "    jsou objednávky přesně za 1 000 Kč, tedy právě ta chyba.\n";
echo "    Poslední řádek je pointa: C a B2 se chovají úplně stejně.\n";
echo "    Liší se jen tím, co po sobě nechají.\n\n";

// --- 4. Jak to vypadá v code review ---------------------------------------

echo "4. Co uvidí recenzent\n\n";

$a  = diffSize(__DIR__ . '/Step0/Shipping.php', __DIR__ . '/PathA/Shipping.php');
$b1 = diffSize(__DIR__ . '/Step0/Shipping.php', __DIR__ . '/PathB1/Shipping.php');
$b2 = diffSize(__DIR__ . '/PathB1/Shipping.php', __DIR__ . '/PathB2/Shipping.php');

printf(
    "    %s%s%s%s\n",
    pad('commit', 34),
    pad('+ řádků', 11),
    pad('- řádků', 11),
    'mění chování?',
);

$c = diffSize(__DIR__ . '/Step0/Shipping.php', __DIR__ . '/PathC/Shipping.php');

printf(
    "    %s%s%s%s\n",
    pad('C: jen oprava', 34),
    pad('+' . $c['added'], 11),
    pad('-' . $c['removed'], 11),
    'ano — a nic víc',
);

printf(
    "    %s%s%s%s\n",
    pad('A: jediný commit', 34),
    pad('+' . $a['added'], 11),
    pad('-' . $a['removed'], 11),
    'ano, někde uvnitř',
);

printf(
    "    %s%s%s%s\n",
    pad('B1: úklid', 34),
    pad('+' . $b1['added'], 11),
    pad('-' . $b1['removed'], 11),
    'ne',
);

printf(
    "    %s%s%s%s\n\n",
    pad('B2: oprava', 34),
    pad('+' . $b2['added'], 11),
    pad('-' . $b2['removed'], 11),
    'ano — a je vidět kde',
);

printf(
    "    Cesta A: recenzent hledá tu jednu podstatnou řádku mezi %d.\n",
    $a['added'] + $a['removed'],
);
printf(
    "    Cesta B: opravný commit má %s — a je to ta chyba.\n\n",
    lines($b2['added'] + $b2['removed']),
);

// --- 5. Odpadek, který odpadek není ---------------------------------------

echo "5. Kde je hranice\n\n";

/** Vyrobí variantu výchozího souboru s jednou provedenou úpravou. */
function variant(callable $edit): string
{
    $code = $edit(file_get_contents(__DIR__ . '/Step0/Shipping.php'));
    $tmp = tempnam(sys_get_temp_dir(), 'variant') . '.php';
    file_put_contents($tmp, $code);

    return $tmp;
}

$withoutImport = variant(static fn (string $c): string => str_replace("use DateTimeImmutable;\n\n", '', $c));

$withoutDeadMethod = variant(static fn (string $c): string => str_replace(
    "\n    private function oldLabel(int \$p): string\n    {\n        return \$p . ' Kc';\n    }\n",
    '',
    $c,
));

$importDiff = diffSize(__DIR__ . '/Step0/Shipping.php', $withoutImport);
$deadDiff = diffSize(__DIR__ . '/Step0/Shipping.php', $withoutDeadMethod);
$tooBig = diffSize(__DIR__ . '/Step0/Shipping.php', __DIR__ . '/TooBig/Shipping.php');
$newFileLines = count(file(stripped(__DIR__ . '/TooBig/ShippingLabel.php')));

@unlink($withoutImport);
@unlink($withoutDeadMethod);

$rows = [
    ['nepoužitý import', $importDiff['added'] + $importDiff['removed'], 'ne'],
    ['mrtvá metoda', $deadDiff['added'] + $deadDiff['removed'], 'ne'],
    ['pojmenování a konstanty', $b1['added'] + $b1['removed'], 'ne'],
    ['rozdělit třídu (SRP)', $tooBig['added'] + $tooBig['removed'] + $newFileLines, 'ANO — všechna volání label()'],
];

printf("    %s%s%s\n", pad('úklid', 34), pad('řádků diffu', 16), 'sáhne mimo soubor?');

foreach ($rows as [$label, $size, $outside]) {
    printf("    %s%s%s\n", pad($label, 34), pad((string) $size, 16), $outside);
}

echo "\n";

echo "    První tři se poznají bez přemýšlení. Poslední je správná\n";
echo "    změna ve špatnou chvíli — čeká na ni zákaznická podpora.\n";
echo "    Fowler na to má odpověď: odlož ji a poznamenej.\n\n";

echo "    „If the refactoring ends up being longer than is reasonable,\n";
echo "     stash the refactoring and come back to it later.\"\n";
echo "                                       — Martin Fowler, 2014\n";
