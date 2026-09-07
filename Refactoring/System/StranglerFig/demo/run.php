<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka techniky Strangler Fig.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Request.php';
require __DIR__ . '/Response.php';
require __DIR__ . '/System.php';
require __DIR__ . '/LegacyMonolith.php';
require __DIR__ . '/NewSystem.php';
require __DIR__ . '/Facade.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function bar(float $percent, int $width = 20): string
{
    $filled = (int) round($percent / 100 * $width);

    return str_repeat('█', $filled) . str_repeat('░', $width - $filled);
}

/** Provoz e-shopu — kolik požadavků na kterou schopnost. */
function traffic(): array
{
    return [
        'katalog'    => 50,
        'košík'      => 25,
        'objednávky' => 15,
        'fakturace'  => 7,
        'reporty'    => 3,
    ];
}

/** Pustí přes fasádu jeden den provozu. */
function runTraffic(Facade $facade): void
{
    $facade->resetCounters();

    foreach (traffic() as $capability => $count) {
        for ($i = 0; $i < $count; ++$i) {
            $facade->handle(new Request($capability, '/' . $capability));
        }
    }
}

function report(Facade $facade, string $label): void
{
    $total = array_sum($facade->servedCount);
    $newPercent = $facade->servedCount['nový'] / $total * 100;

    printf(
        "    %s%s %5.1f %%   zbývá na starém: %s\n",
        pad($label, 24),
        bar($newPercent),
        $newPercent,
        $facade->stillOnLegacy() === [] ? '—' : implode(', ', $facade->stillOnLegacy()),
    );
}

echo "=== Strangler Fig ===\n\n";

$legacy = new LegacyMonolith(['katalog', 'košík', 'objednávky', 'fakturace', 'reporty']);
$new = new NewSystem();
$facade = new Facade($legacy, $new);

// --- 1. Fasáda na začátku ---------------------------------------------------

echo "1. Krok 1: fasáda, která zatím nic nemění\n\n";

runTraffic($facade);

printf("    %s%d\n", pad('schopností ve starém', 26), count($legacy->capabilities()));
printf("    %s%d\n", pad('schopností v novém', 26), count($new->capabilities()));
printf("    %s%d z %d\n\n", pad('obslouženo starým', 26), $facade->servedCount['legacy'], array_sum($facade->servedCount));

echo "    Fasáda existuje, ale všechno pouští dál na starý systém.\n";
echo "    Chování se nezměnilo — a to je celý smysl prvního kroku.\n\n";

// --- 2. Postupné přesouvání -------------------------------------------------

echo "2. Kroky 2–6: schopnosti se přesouvají po jedné\n\n";

report($facade, 'na začátku');

// Pořadí je záměrné: začíná se tím, co je nejmíň rizikové.
foreach (['reporty', 'fakturace', 'objednávky', 'košík', 'katalog'] as $capability) {
    $new->implement($capability);
    $facade->route($capability);
    runTraffic($facade);

    report($facade, 'po přesunu: ' . $capability);
}

echo "\n    Pořadí není náhodné. Reporty jsou jen pro čtení a jejich\n";
echo "    výpadek nikdo neuvidí; katalog je 50 % provozu a jde\n";
echo "    poslední. Začíná se tím, co se nejlíp vrací.\n\n";

// --- 3. Kdy se dá starý systém vypnout -------------------------------------

echo "3. Kdy se dá starý systém vypnout\n\n";

printf("    %s%s\n", pad('schopností stále na starém', 32), count($facade->stillOnLegacy()) === 0 ? 'žádná' : implode(', ', $facade->stillOnLegacy()));
printf("    %s%d %%\n", pad('provozu na starém', 32), $facade->servedCount['legacy'] / array_sum($facade->servedCount) * 100);
printf("    %s%s\n\n", pad('lze vypnout?', 32), $facade->stillOnLegacy() === [] ? 'ano' : 'ne');

foreach ($facade->stillOnLegacy() as $capability) {
    $legacy->retire($capability);
}

foreach (['reporty', 'fakturace', 'objednávky', 'košík', 'katalog'] as $capability) {
    $legacy->retire($capability);
}

printf("    po vyřazení schopností má starý systém: %d\n\n", count($legacy->capabilities()));

echo "    Teprve teď se smí vypnout. Ne dřív — dokud fasáda posílá\n";
echo "    byť jedinou schopnost na starý systém, musí běžet.\n\n";

// --- 4. Návratová cesta -----------------------------------------------------

echo "4. Návrat zpět u jedné schopnosti\n\n";

$legacy2 = new LegacyMonolith(['katalog', 'košík', 'objednávky', 'fakturace', 'reporty']);
$new2 = new NewSystem();
$facade2 = new Facade($legacy2, $new2);

$new2->implement('objednávky');
$facade2->route('objednávky');
runTraffic($facade2);

$before = $facade2->servedCount['nový'];

// Něco se pokazilo — vrátíme jednu schopnost zpět.
$facade2->route('objednávky', toNew: false);
runTraffic($facade2);

printf("    %s%d požadavků\n", pad('po přesunu objednávek', 30), $before);
printf("    %s%d požadavků\n\n", pad('po návratu zpět', 30), $facade2->servedCount['nový']);

echo "    Vrací se jedna schopnost, ne celá migrace. Ostatní přesuny\n";
echo "    zůstávají — a to je hlavní rozdíl proti přepisu naráz.\n\n";

// --- 5. Čím se to liší od Branch by Abstraction ---------------------------

echo "5. Čím se to liší od Branch by Abstraction\n\n";

printf("    %s%s%s\n", pad('', 26), pad('Branch by Abstraction', 26), 'Strangler Fig');
printf("    %s%s%s\n", pad('kde vede šev', 26), pad('uvnitř kódu', 26), 'na hranici systému');
printf("    %s%s%s\n", pad('co se vyměňuje', 26), pad('implementace téhož', 26), 'celé schopnosti');
printf("    %s%s%s\n", pad('společné rozhraní', 26), pad('nutné', 26), 'nemusí být');
printf("    %s%s%s\n", pad('starý a nový kód', 26), pad('v jednom procesu', 26), 'často oddělené systémy');
printf("    %s%s%s\n\n", pad('konec', 26), pad('smazání staré třídy', 26), 'vypnutí starého systému');

echo "    Zjednodušeně: Branch by Abstraction vyměňuje součástku,\n";
echo "    Strangler Fig staví nový dům kolem starého.\n";
