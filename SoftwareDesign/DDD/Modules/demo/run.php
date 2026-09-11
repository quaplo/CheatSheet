<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Modules.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/ModuleScanner.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function violations(int $n): string
{
    return match (true) {
        $n === 0 => 'žádné porušení',
        $n === 1 => '1 porušení',
        $n < 5 => $n . ' porušení',
        default => $n . ' porušení',
    };
}

echo "=== Modules ===\n\n";

// --- 1. Jména modulů vyprávějí, co systém dělá ----------------------------

echo "1. „Choose modules that tell the story of the system\"\n\n";

printf("    %s%s\n", pad('dělení podle vrstev', 26), implode(', ', ['Application', 'Domain', 'Infrastructure']));
printf("    %s%s\n\n", pad('dělení podle modulů', 26), implode(', ', ModuleScanner::modulesIn(__DIR__ . '/Good')));

echo "    První řádek ti řekne, jak je to postavené. Druhý, co to je.\n";
echo "    Evans chce ten druhý — a jména modulů považuje za součást\n";
echo "    jednotného jazyka, ne za technický detail.\n\n";

// --- 2. Hranice, kterou PHP nehlídá ---------------------------------------

echo "2. Hranice, kterou ti jazyk neuhlídá\n\n";

$bad = ModuleScanner::violationsIn(__DIR__ . '/Bad');
$good = ModuleScanner::violationsIn(__DIR__ . '/Good');

echo "    Pravidlo: do cizího modulu se smí jen přes jeho Api\\.\n\n";

printf("    %s%s\n", pad('varianta Bad', 26), violations(count($bad)));

foreach ($bad as $violation) {
    printf("      · %s\n        → %s\n", $violation['from'], $violation['to']);
}

printf("\n    %s%s\n\n", pad('varianta Good', 26), violations(count($good)));

echo "    Obě varianty se spustí a obě projdou `php -l`. Rozdíl mezi\n";
echo "    nimi nepozná jazyk — pozná ho až tenhle skript.\n\n";

// --- 3. Kolik toho modul o ostatních ví -----------------------------------

echo "3. Nízká provázanost, měřená\n\n";

/** Kolik cizích modulů se z daného modulu volá a přes co. */
function couplingOf(string $dir): array
{
    $coupling = [];

    foreach (ModuleScanner::modulesIn($dir) as $module) {
        $coupling[$module] = ['api' => [], 'vnitřek' => []];
    }

    foreach (glob($dir . '/*/*/*.php') as $file) {
        $code = file_get_contents($file);
        preg_match('/^namespace\s+([^;]+);/m', $code, $ns);
        $from = explode('\\', $ns[1] ?? '')[1] ?? null;

        preg_match_all('/^use\s+([^;]+);/m', $code, $uses);

        foreach ($uses[1] as $import) {
            $parts = explode('\\', $import);
            $to = $parts[1] ?? null;

            if ($to === null || $to === $from) {
                continue;
            }

            $kind = ($parts[2] ?? null) === 'Api' ? 'api' : 'vnitřek';
            $coupling[$from][$kind][$to] = true;
        }
    }

    return $coupling;
}

foreach (['Bad' => 'Bad', 'Good' => 'Good'] as $label => $dir) {
    printf("    %s\n", 'varianta ' . $label);
    printf("      %s%s%s\n", pad('modul', 14), pad('zná přes Api', 22), 'zná vnitřek');

    foreach (couplingOf(__DIR__ . '/' . $dir) as $module => $known) {
        printf(
            "      %s%s%s\n",
            pad($module, 14),
            pad($known['api'] === [] ? '—' : implode(', ', array_keys($known['api'])), 22),
            $known['vnitřek'] === [] ? '—' : implode(', ', array_keys($known['vnitřek'])),
        );
    }

    echo "\n";
}

echo "    Pravý sloupec je to, co Evans myslí vysokou provázaností.\n";
echo "    Není to o počtu závislostí — je o tom, kolik toho o sobě\n";
echo "    moduly musejí vědět, aby fungovaly.\n\n";

// --- 4. Když to nejde rozplést -------------------------------------------

echo "4. A co když se ty závislosti rozplést nedají\n\n";

echo "    Evansova odpověď není „přesuň soubory\":\n\n";
echo "    „…if it doesn't [yield low coupling] look for a way to\n";
echo "     CHANGE THE MODEL to disentangle the concepts, or an\n";
echo "     overlooked concept that might be the basis of a module\n";
echo "     that would bring the elements together in a meaningful way.\"\n\n";

echo "    Modul, který drží jen díky tomu, že ostatní sahají dovnitř,\n";
echo "    není špatně rozdělený kód. Je to špatně pochopená doména.\n";
