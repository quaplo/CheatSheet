<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Factory Method.
 *
 * Spuštění:  php run.php
 *
 * Demo má dvě části, protože se pod jedním jménem míchají dvě věci:
 *   A) pojmenované konstruktory — to, co v PHP píšeš denně
 *   B) GoF Factory Method — o vytvoření rozhoduje potomek
 */

require __DIR__ . '/Named/Money.php';
require __DIR__ . '/Gof/Document.php';
require __DIR__ . '/Gof/CsvDocument.php';
require __DIR__ . '/Gof/JsonDocument.php';
require __DIR__ . '/Gof/ExportJob.php';
require __DIR__ . '/Gof/CsvExportJob.php';
require __DIR__ . '/Gof/JsonExportJob.php';

use Gof\CsvExportJob;
use Gof\ExportJob;
use Gof\JsonExportJob;
use Named\Money;

echo "=== Factory Method ===\n\n";

// ==========================================================================
// A. Pojmenované konstruktory
// ==========================================================================

echo "A. Pojmenované konstruktory — to, co píšeš denně\n\n";

echo "1. Jeden konstruktor nestačí\n\n";
echo "        new Money(129000)   ← koruny? haléře? kdo ví\n\n";

foreach ([
    "Money::fromCents(129000)"    => Money::fromCents(129000),
    "Money::fromCrowns(1290.00)"  => Money::fromCrowns(1290.00),
    "Money::fromString('1 290 Kč')" => Money::fromString('1 290 Kč'),
    "Money::zero()"               => Money::zero(),
] as $call => $money) {
    printf("        %s %s\n", mb_str_pad($call, 32), $money->format());
}

echo "\n    Čtyři cesty dovnitř, každá s vlastním jménem — a všechny\n";
echo "    končí u jednoho privátního konstruktoru, takže pravidlo\n";
echo "    o platnosti je pořád na jednom místě.\n";

echo "\n2. A neplatný vstup neprojde\n";

try {
    Money::fromString('nic takového');
} catch (InvalidArgumentException $e) {
    printf("\n        %s\n", $e->getMessage());
}

echo "\n    Parsování patří do továrny. Konstruktor zůstane hloupý\n";
echo "    a poslední — jen přiřadí.\n";

// ==========================================================================
// B. GoF Factory Method
// ==========================================================================

echo "\n\nB. GoF Factory Method — o vytvoření rozhoduje potomek\n\n";

$rows = [
    ['sku' => 'MON-27', 'nazev' => 'Monitor 27"', 'cena' => 7990],
    ['sku' => 'KLA-01', 'nazev' => 'Klávesnice', 'cena' => 1290],
];

/** Volající zná jen ExportJob. Neví, jaký dokument z toho vypadne. */
function export(ExportJob $job, array $rows): void
{
    foreach (explode("\n", $job->run($rows)) as $line) {
        echo '        ' . $line . "\n";
    }
}

echo "3. Táž kostra, jiný výsledek\n\n";
export(new CsvExportJob(), $rows);
echo "\n";
export(new JsonExportJob(), $rows);

echo "\n    ExportJob::run() zná celý postup — vyrenderuj, sestav jméno\n";
echo "    souboru, změř velikost. Jen neví, JAKÝ dokument vzniká.\n";
echo "    To doplní podtřída metodou createDocument().\n";

// --- 4. Rozdíl mezi oběma -------------------------------------------------

echo "\n4. Nezaměňovat\n\n";
printf("    %s %s %s\n", mb_str_pad('', 22), mb_str_pad('pojmenovaný konstruktor', 26), 'GoF Factory Method');
printf("    %s %s %s\n", mb_str_pad('kdo rozhoduje', 22), mb_str_pad('volající', 26), 'POTOMEK');
printf("    %s %s %s\n", mb_str_pad('kde je', 22), mb_str_pad('statická metoda třídy', 26), 'metoda v hierarchii');
printf("    %s %s %s\n", mb_str_pad('co vrací', 22), mb_str_pad('vlastní typ (self)', 26), 'rozhraní produktu');
printf("    %s %s %s\n", mb_str_pad('potřebuje dědičnost', 22), mb_str_pad('ne', 26), 'ANO');
printf("    %s %s %s\n", mb_str_pad('jak často v PHP', 22), mb_str_pad('pořád', 26), 'zřídka');

// --- 5. Kdy to DI kontejner udělá za tebe ---------------------------------

echo "\n5. Kdy GoF variantu nepotřebuješ\n\n";
echo "        // GoF: dědičnost rozhoduje, co vznikne\n";
echo "        final class CsvExportJob extends ExportJob {\n";
echo "            protected function createDocument(): Document { return new CsvDocument(); }\n";
echo "        }\n\n";
echo "        // Injektáž: rozhodne DI kontejner, žádná hierarchie\n";
echo "        final readonly class ExportJob {\n";
echo "            public function __construct(private Document \$document) {}\n";
echo "        }\n\n";
echo "    Druhá varianta je v moderním PHP skoro vždycky lepší:\n";
echo "    kratší, bez dědičnosti, a `Document` jde v testu podstrčit.\n";
echo "    GoF Factory Method má smysl, když potomek musí rozhodnout\n";
echo "    sám a kontejner o té volbě nemůže vědět.\n";
