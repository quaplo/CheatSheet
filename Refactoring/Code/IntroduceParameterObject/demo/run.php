<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka refaktoringu Introduce Parameter Object.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Before/SalesReport.php';
require __DIR__ . '/After/DateRange.php';
require __DIR__ . '/After/SalesReport.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function places(int $n): string
{
    return match (true) {
        $n === 1 => '1 místo',
        $n < 5 => $n . ' místa',
        default => $n . ' míst',
    };
}

/**
 * Kolik parametrů mají dohromady vyjmenované metody.
 *
 * Záměrně jen ty tři, které jsou v obou verzích — dailyAverageFor()
 * v Before neexistuje a do porovnání by nepatřil.
 *
 * @param list<string> $methods
 */
function parametersOf(string $class, array $methods): int
{
    $total = 0;

    foreach ($methods as $method) {
        $total += (new ReflectionMethod($class, $method))->getNumberOfParameters();
    }

    return $total;
}

/** Kolikrát je v souboru napsané pravidlo „spadá do období". */
function rangeChecks(string $file): int
{
    return substr_count(file_get_contents($file), ">= \$fromTs")
        + substr_count(file_get_contents($file), ">= \$this->fromTs");
}

$day = 86400;
$now = 1_700_000_000;

$orders = [
    ['paidAt' => $now - 25 * $day, 'totalInCents' => 129000],
    ['paidAt' => $now - 10 * $day, 'totalInCents' => 45000],
    ['paidAt' => $now - 3 * $day, 'totalInCents' => 98000],
    ['paidAt' => $now - 1 * $day, 'totalInCents' => 12000],
];

$from = $now - 14 * $day;
$to = $now;

$before = new Before\SalesReport($orders);
$after = new After\SalesReport($orders);

echo "=== Introduce Parameter Object ===\n\n";

echo "    Tři metody, tři stejné dvojice parametrů:\n\n";
echo "        totalFor(int \$fromTs, int \$toTs)\n";
echo "        countFor(int \$fromTs, int \$toTs)\n";
echo "        averageFor(int \$fromTs, int \$toTs)\n\n";

// --- 1. Chování ------------------------------------------------------------

echo "1. Chování se nezměnilo\n\n";

$period = new After\DateRange($from, $to);

$comparisons = [
    'totalFor' => [$before->totalFor($from, $to), $after->totalFor($period)],
    'countFor' => [$before->countFor($from, $to), $after->countFor($period)],
    'averageFor' => [$before->averageFor($from, $to), $after->averageFor($period)],
];

printf("    %s%s%s\n", pad('metoda', 16), pad('předtím', 12), 'potom');

foreach ($comparisons as $method => [$b, $a]) {
    printf("    %s%s%s\n", pad($method, 16), pad((string) $b, 12), $a === $b ? (string) $a : 'LIŠÍ SE: ' . $a);
}

echo "\n";

// --- 2. Co se zmenšilo -----------------------------------------------------

echo "2. Co se zmenšilo\n\n";

printf("    %s%s%s\n", pad('', 38), pad('předtím', 12), 'potom');
$shared = ['totalFor', 'countFor', 'averageFor'];

printf(
    "    %s%s%d\n",
    pad('parametrů v týchž třech metodách', 38),
    pad((string) parametersOf(Before\SalesReport::class, $shared), 12),
    parametersOf(After\SalesReport::class, $shared),
);
printf(
    "    %s%s%s\n\n",
    pad('míst, která znají „spadá do období"', 38),
    pad(places(rangeChecks(__DIR__ . '/Before/SalesReport.php')), 12),
    places(rangeChecks(__DIR__ . '/After/DateRange.php') + rangeChecks(__DIR__ . '/After/SalesReport.php')),
);

// --- 3. Prohozené argumenty ------------------------------------------------

echo "3. Teď to zajímavé: prohozené argumenty\n\n";

echo "    Někdo se v call site uklepne a zamění pořadí.\n\n";

$beforeSwapped = $before->totalFor($to, $from);

printf("    %s%s\n", pad('předtím: totalFor($to, $from)', 38), 'vrátí ' . $beforeSwapped . ', bez chyby');

try {
    new After\DateRange($to, $from);
    $afterSwapped = 'projde — ukázka je rozbitá';
} catch (InvalidArgumentException $e) {
    $afterSwapped = 'InvalidArgumentException hned při sestavení';
}

printf("    %s%s\n\n", pad('potom: new DateRange($to, $from)', 38), $afterSwapped);

printf(
    "    Správný výsledek je %d. Verze bez objektu vrátila %d a nikde\n    se nic nestalo — ta chyba by odešla do reportu.\n\n",
    $before->totalFor($from, $to),
    $beforeSwapped,
);

echo "    Objekt tu nezachrání typový systém — obě hodnoty jsou int.\n";
echo "    Zachrání to konstruktor: je jediné místo, kde se dá tahle\n";
echo "    podmínka zkontrolovat, a proto se tam vejde.\n\n";

// --- 4. Co se do objektu přistěhovalo -------------------------------------

echo "4. Proč se to vyplatí až potom\n\n";

echo "    Samotné seskupení parametrů je jen úklid podpisu. Teprve\n";
echo "    když má objekt kam přijmout chování, začne se to vracet:\n\n";

foreach ((new ReflectionClass(After\DateRange::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    if ($method->isConstructor()) {
        continue;
    }

    printf("      · DateRange::%s()\n", $method->getName());
}

echo "\n    A díky days() šlo přidat metodu, která by předtím musela\n";
echo "    počítat dny sama:\n\n";

printf("        dailyAverageFor(\$period)  →  %d haléřů denně\n\n", $after->dailyAverageFor($period));

echo "    Tady je ten rozdíl vidět na jednom čísle: dailyAverageFor()\n";
echo "    má tři řádky, protože days() už existuje. Ve verzi bez\n";
echo "    objektu by si musela počítat dny sama — a byla by to čtvrtá\n";
echo "    metoda, která zná vnitřek období.\n";
