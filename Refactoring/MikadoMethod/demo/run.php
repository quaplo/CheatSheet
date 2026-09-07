<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka Mikado metody.
 *
 * Demo skutečně provede Mikado smyčku: zkusí změnu naivně, přečte,
 * co spadlo, vrátí to zpátky a z nálezů poskládá graf. Teprve pak
 * ho projde od listů.
 *
 * Spuštění:  php run.php
 */

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function files(int $n): string
{
    return match (true) {
        $n === 1 => '1 soubor',
        $n < 5 => $n . ' soubory',
        default => $n . ' souborů',
    };
}

/**
 * Pokus: vezmi výchozí stav, přepiš v něm jeden soubor a spusť kontrolu.
 *
 * @return array{ok: bool, error: string}
 */
function experiment(string $file, string $fromState): array
{
    $dir = sys_get_temp_dir() . '/mikado-' . bin2hex(random_bytes(4));
    mkdir($dir);

    foreach (glob(__DIR__ . '/states/00-vychozi/*.php') as $source) {
        copy($source, $dir . '/' . basename($source));
    }

    copy(__DIR__ . '/states/' . $fromState . '/' . $file, $dir . '/' . $file);

    $output = [];
    $code = 0;
    exec(sprintf('php %s %s 2>&1', escapeshellarg(__DIR__ . '/check.php'), escapeshellarg($dir)), $output, $code);

    // Revert — smyčka končí tím, že se pokus zahodí.
    shell_exec('rm -rf ' . escapeshellarg($dir));

    $errors = array_map(
        static fn (string $line): string => str_replace([realpath($dir) . '/', $dir . '/'], '', $line),
        array_filter($output),
    );

    return ['ok' => $code === 0, 'errors' => $errors];
}

/** Kolik souborů se liší mezi dvěma stavy. */
function changedFiles(string $from, string $to): int
{
    $changed = 0;

    foreach (glob(__DIR__ . '/states/' . $to . '/*.php') as $file) {
        $counterpart = __DIR__ . '/states/' . $from . '/' . basename($file);

        if (!is_file($counterpart) || md5_file($file) !== md5_file($counterpart)) {
            ++$changed;
        }
    }

    return $changed;
}

echo "=== Mikado metoda ===\n\n";

echo "    Cíl: nikdo nevolá Config::vatPercent() staticky.\n";
echo "    Jak se tam dostat, zatím nikdo neví. Mikado to nezjišťuje\n";
echo "    čtením kódu, ale pokusy.\n\n";

// --- 1. Pokusy -------------------------------------------------------------

echo "1. Pokus, chyba, revert\n\n";

$experiments = [
    ['cíl: zrušit Config::vatPercent()', 'Config.php', '05-cil'],
    ['PriceCalculator dostane DPH', 'PriceCalculator.php', '04-calculator'],
    ['OrderService dostane DPH', 'OrderService.php', '03-service'],
    ['OrderReport dostane DPH', 'OrderReport.php', '01-report'],
    ['Invoice dostane DPH', 'Invoice.php', '02-invoice'],
];

$results = [];

foreach ($experiments as [$label, $file, $state]) {
    $result = experiment($file, $state);
    $results[$label] = $result;

    printf("    %s%s\n", pad($label, 36), $result['ok'] ? 'PROŠLO — je to list' : 'spadlo');

    foreach ($result['errors'] as $error) {
        printf("      · %s\n", $error);
    }
}

$failed = count(array_filter($results, static fn (array $r): bool => !$r['ok']));
$passed = count($results) - $failed;

printf(
    "\n    Pokusů, které spadly a skončily revertem: %d.\n    Pokusů, které prošly a rovnou se commitnou: %d — to jsou listy.\n",
    $failed,
    $passed,
);

echo "\n    Revert je na metodě to, co lidem vadí nejvíc, a zároveň to,\n";
echo "    proč funguje: kód se nikdy needituje ve stavu, o kterém\n";
echo "    nevíš, jak na tom je.\n\n";

printf(
    "    Všimni si prvního pokusu: dvě hlášky, ale jedna příčina —\n    a tedy jeden předpoklad, ne dva.\n\n",
);

// --- 2. Graf ---------------------------------------------------------------

echo "2. Co z těch chyb vznikne\n\n";

echo "    Chybové hlášky ukázaly, co komu překáží. Z toho je graf:\n\n";
echo "      Cíl: nikdo nevolá Config::vatPercent() staticky\n";
echo "      └── PriceCalculator dostane DPH v konstruktoru\n";
echo "          ├── OrderService dostane DPH v konstruktoru\n";
echo "          │   └── OrderReport dostane DPH v konstruktoru   ← list\n";
echo "          └── Invoice dostane DPH v konstruktoru           ← list\n\n";

echo "    Graf se nekreslil dopředu. Vypadl z toho, co spadlo.\n\n";

// --- 3. Průchod od listů ---------------------------------------------------

echo "3. Průchod od listů ke kořeni\n\n";

$walk = [
    ['00-vychozi', 'výchozí stav', ''],
    ['01-report', 'OrderReport dostane DPH', 'list'],
    ['02-invoice', 'Invoice dostane DPH', 'list'],
    ['03-service', 'OrderService dostane DPH', ''],
    ['04-calculator', 'PriceCalculator dostane DPH', ''],
    ['05-cil', 'Config::vatPercent() zrušena', 'CÍL'],
];

printf("    %s%s%s%s\n", pad('krok', 34), pad('', 7), pad('změněno', 12), 'kontrola');

$previous = null;
$allGreen = true;

foreach ($walk as [$state, $label, $note]) {
    $output = [];
    $code = 0;
    exec(sprintf(
        'php %s %s 2>&1',
        escapeshellarg(__DIR__ . '/check.php'),
        escapeshellarg(__DIR__ . '/states/' . $state),
    ), $output, $code);

    $allGreen = $allGreen && $code === 0;
    $changed = $previous === null ? 0 : changedFiles($previous, $state);

    printf(
        "    %s%s%s%s\n",
        pad($label, 34),
        pad($note, 7),
        pad($previous === null ? '—' : files($changed), 12),
        $code === 0 ? 'prošla' : 'SPADLA',
    );

    $previous = $state;
}

echo "\n";

printf("    %s%s\n", pad('kroků celkem', 34), (string) (count($walk) - 1));
printf("    %s%s\n\n", pad('kolikrát byl kód rozbitý', 34), $allGreen ? '0' : 'alespoň jednou');

// --- 4. Proč ne prorazit rovnou -------------------------------------------

echo "4. Proč to nezkusit prorazit rovnou\n\n";

$atOnce = changedFiles('00-vychozi', '05-cil');
$largestStep = 0;

for ($i = 1, $n = count($walk); $i < $n; ++$i) {
    $largestStep = max($largestStep, changedFiles($walk[$i - 1][0], $walk[$i][0]));
}

printf("    %s%s\n", pad('naráz od výchozího stavu k cíli', 36), files($atOnce));
printf("    %s%s\n\n", pad('největší jednotlivý krok Mikada', 36), files($largestStep));

printf(
    "    Prorazit to znamená držet %s v hlavě naráz a nemít\n    mezitím funkční kód. Mikado to rozdělí tak, že největší\n    skok je %s — a po každém se dá odejít.\n\n",
    files($atOnce),
    files($largestStep),
);

echo "    „When there are errors, you should always roll back all changes.\n";
echo "     This is extremely important! Editing code in an unknown state\n";
echo "     is very error-prone.\"\n";
echo "                        — Ellnestam, Brolund: The Mikado Method, 2014\n";
