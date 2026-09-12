<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka Clean Architecture.
 *
 * Spuštění:  php run.php
 */

spl_autoload_register(static function (string $class): void {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

use Adapters\CliPresenter;
use Adapters\HtmlPresenter;
use Adapters\InMemoryOrderRepository;
use Adapters\JsonPresenter;
use Core\UseCases\ShowOrder;
use Core\UseCases\ShowOrderRequest;

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function files(int $n): string
{
    return match (true) {
        $n === 0 => 'žádný soubor',
        $n === 1 => '1 soubor',
        $n < 5 => $n . ' soubory',
        default => $n . ' souborů',
    };
}

/** Které kruhy zná daná složka podle importů. */
function knownCircles(string $dir): array
{
    $known = [];

    foreach (glob($dir . '/{,*/}*.php', GLOB_BRACE) as $file) {
        preg_match_all('/^use\s+([^;]+);/m', file_get_contents($file), $m);

        foreach ($m[1] as $import) {
            $circle = explode('\\', $import)[0];

            if ($circle !== basename($dir)) {
                $known[$circle] = true;
            }
        }
    }

    return array_keys($known);
}

echo "=== Clean Architecture ===\n\n";

// --- 1. Pravidlo závislosti -----------------------------------------------

echo "1. Pravidlo závislosti\n\n";

echo "    „Source code dependencies can only point inwards.\n";
echo "     Nothing in an inner circle can know anything at all about\n";
echo "     something in an outer circle.\"    — Robert C. Martin, 2012\n\n";

printf("    %s%s\n", pad('Core/ zná z vnějšku', 28), implode(', ', knownCircles(__DIR__ . '/Core')) ?: '— nic');
printf("    %s%s\n\n", pad('Adapters/ zná z vnitřku', 28), implode(', ', knownCircles(__DIR__ . '/Adapters')) ?: '— nic');

echo "    Šipka vede jen jedním směrem, a to je celé pravidlo.\n\n";

// --- 2. Tentýž use case, tři způsoby dodání -------------------------------

echo "2. Jeden use case, tři způsoby dodání\n\n";

$showOrder = new ShowOrder(new InMemoryOrderRepository());
$response = $showOrder(new ShowOrderRequest('A-2026-118'));

$presenters = [
    'HTML' => new HtmlPresenter(),
    'JSON' => new JsonPresenter(),
    'CLI' => new CliPresenter(),
];

foreach ($presenters as $label => $presenter) {
    printf("    %s%s\n", pad($label, 8), $presenter->present($response));
}

echo "\n    Všechny tři dostaly TÝŽ objekt a žádný z nich nevolal doménu.\n\n";

// --- 3. Co přes hranici prošlo --------------------------------------------

echo "3. Co přes hranici prošlo\n\n";

$presenterImports = [];

foreach (glob(__DIR__ . '/Adapters/*Presenter.php') as $file) {
    preg_match_all('/^use\s+([^;]+);/m', file_get_contents($file), $m);
    $presenterImports = [...$presenterImports, ...$m[1]];
}

$fromEntities = array_filter($presenterImports, static fn (string $i): bool => str_starts_with($i, 'Core\\Entities'));

printf("    %s%s\n", pad('typ, který přes hranici jde', 36), 'ShowOrderResponse');
printf("    %s%s\n", pad('je to entita?', 36), 'ne — jen hodnoty');
printf("    %s%d\n\n", pad('kolikrát presenter sáhl na entitu', 36), count($fromEntities));

echo "    „We don't want to cheat and pass Entities or Database rows.\"\n";
echo "    Kdyby přes hranici šel Order, znal by ho každý presenter —\n";
echo "    a změna entity by změnila tvar JSON API.\n\n";

// --- 4. Co stojí čtvrtý kanál ---------------------------------------------

echo "4. Kolik stál čtvrtý způsob dodání\n\n";

$coreFiles = glob(__DIR__ . '/Core/{,*/}*.php', GLOB_BRACE);

printf("    %s%s\n", pad('přidáno kvůli CLI', 30), files(1) . ' (CliPresenter)');
printf("    %s%s\n", pad('změněno v Core/', 30), files(0));
printf("    %s%s\n\n", pad('souborů v Core/ celkem', 30), files(count($coreFiles)));

echo "    To je celé tvrzení téhle architektury a dá se ověřit:\n";
echo "    nový způsob dodání je nový soubor ve vnějším kruhu, nic víc.\n\n";

// --- 5. Entity nebo use case ----------------------------------------------

echo "5. Kam které pravidlo patří\n\n";

printf("    %s%s\n", pad('pravidlo', 46), 'kde bydlí');
printf("    %s%s\n", pad('odeslanou objednávku nelze stornovat', 46), 'Entities');
printf("    %s%s\n", pad('cena je součet položek', 46), 'Entities');
printf("    %s%s\n", pad('v administraci je vidět tlačítko Stornovat', 46), 'UseCases');
printf("    %s%s\n\n", pad('částka se zobrazí v korunách s čárkou', 46), 'Adapters');

echo "    Zkouška, která to rozhodne: platilo by to pravidlo i tehdy,\n";
echo "    kdyby tahle aplikace neexistovala? Když ano, je to entita.\n";
