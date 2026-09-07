<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka techniky Expand–Contract.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Database.php';
require __DIR__ . '/OldApp.php';
require __DIR__ . '/NewApp.php';
require __DIR__ . '/Migration.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/**
 * Zkusí, jestli daná verze aplikace v téhle fázi funguje.
 *
 * Tohle je celé měřítko techniky: během nasazování běží obě verze
 * vedle sebe, takže obě musí fungovat v každé fázi.
 */
function works(callable $action): string
{
    try {
        $action();

        return 'funguje';
    } catch (Throwable $e) {
        return 'SPADNE';
    }
}

echo "=== Expand–Contract ===\n\n";

// --- Výchozí stav ----------------------------------------------------------

$db = new Database();
$old = new OldApp($db);
$migration = new Migration($db);

// Historická data, se kterými se bude muset něco udělat.
for ($i = 1; $i <= 250; ++$i) {
    $old->place(sprintf('2026/%04d', $i));

    if ($i % 3 === 0) {
        $old->markShipped(sprintf('2026/%04d', $i));
    }
}

echo "1. Fáze 0: výchozí stav\n\n";

printf("    %s%s\n", pad('sloupce', 20), implode(', ', $db->columns()));
printf("    %s%d\n", pad('objednávek', 20), $db->count());
printf("    %s%s\n", pad('stará aplikace', 20), works(static fn () => $old->isShipped('2026/0003')));

$newBefore = new NewApp($db);
printf("    %s%s   ← sloupec status neexistuje\n\n", pad('nová aplikace', 20), works(static fn () => $newBefore->statusOf('2026/0003')));

echo "    Skok rovnou na novou verzi by tady rozbil produkci.\n";
echo "    Proto se to dělí na fáze.\n\n";

// --- Fáze 1: Expand --------------------------------------------------------

echo "2. Fáze 1 — EXPAND: přidat sloupec, nic nemazat\n\n";

$migration->expand();
$new = new NewApp($db, dualWrite: true);

printf("    %s%s\n", pad('sloupce', 20), implode(', ', $db->columns()));
printf("    %s%s\n", pad('stará aplikace', 20), works(static fn () => $old->isShipped('2026/0003')));
printf("    %s%s\n", pad('nová aplikace', 20), works(static fn () => $new->statusOf('2026/0003')));
printf("    %s%d z %d\n\n", pad('vyplněný status', 20), $db->count("status != ''"), $db->count());

echo "    Obě verze fungují. Historická data ale status nemají —\n";
echo "    nový sloupec je zatím prázdný.\n\n";

// --- Dvojí zápis -----------------------------------------------------------

echo "3. Dvojí zápis: nová verze píše do obou sloupců\n\n";

$new->place('2026/9001');
$new->markShipped('2026/9001');

printf("    %s%s\n", pad('nová zapsala status', 24), $new->statusOf('2026/9001'));
printf("    %s%s   ← stará verze to taky vidí\n\n", pad('stará čte shipped', 24), $old->isShipped('2026/9001') ? 'expedovaná' : 'nová');

echo "    Bez dvojího zápisu by objednávky založené novou verzí byly\n";
echo "    pro starou neviditelné — a ta zatím pořád běží.\n\n";

// --- Fáze 2: Backfill ------------------------------------------------------

echo "4. Fáze 2 — BACKFILL: doplnit historii po dávkách\n\n";

$start = hrtime(true);
$migrated = $migration->backfill(batchSize: 100);
$ms = (hrtime(true) - $start) / 1_000_000;

printf("    %s%d\n", pad('doplněno řádků', 24), $migrated);
printf("    %s%d\n", pad('velikost dávky', 24), 100);
printf("    %s%.1f ms\n", pad('trvalo', 24), $ms);
printf("    %s%d z %d\n\n", pad('vyplněný status', 24), $db->count("status != ''"), $db->count());

printf("    %s%s\n", pad('stará aplikace', 24), works(static fn () => $old->isShipped('2026/0003')));
printf("    %s%s\n\n", pad('nová aplikace', 24), works(static fn () => $new->statusOf('2026/0003')));

echo "    Po dávkách, ne jedním UPDATE. U milionu řádků by jeden\n";
echo "    příkaz zamkl tabulku na minuty a provoz by stál.\n\n";

// --- Fáze 3: Migrate -------------------------------------------------------

echo "5. Fáze 3 — MIGRATE: čtení přepnuto na nový sloupec\n\n";

printf("    %s%s\n", pad('nová čte status', 24), $new->statusOf('2026/0003'));
printf("    %s%s\n", pad('stará aplikace', 24), works(static fn () => $old->isShipped('2026/0003')));
printf("    %s%s\n\n", pad('nová aplikace', 24), works(static fn () => $new->statusOf('2026/0003')));

echo "    Nová verze umí i to, co stará neuměla:\n";

$new->cancel('2026/0006');
printf("        zrušení objednávky:  %s\n\n", $new->statusOf('2026/0006'));

echo "    Tady se dá zastavit na libovolně dlouho. Dokud běží dvojí\n";
echo "    zápis, obě verze si rozumí — a návrat zpět nic nestojí.\n\n";

// --- Fáze 4: Contract ------------------------------------------------------

echo "6. Fáze 4 — CONTRACT: odstranit starý sloupec\n\n";

$newOnly = new NewApp($db, dualWrite: false);
$migration->contract();

printf("    %s%s\n", pad('sloupce', 20), implode(', ', $db->columns()));
printf("    %s%s   ← teprve teď\n", pad('stará aplikace', 20), works(static fn () => $old->isShipped('2026/0003')));
printf("    %s%s\n\n", pad('nová aplikace', 20), works(static fn () => $newOnly->statusOf('2026/0003')));

echo "    Až tenhle krok starou verzi vyřadí — a smí přijít teprve\n";
echo "    tehdy, když už nikde neběží.\n\n";

// --- Přehled ---------------------------------------------------------------

echo "7. Přehled: kdy co funguje\n\n";

printf("    %s%s%s\n", pad('fáze', 26), pad('stará verze', 16), 'nová verze');
printf("    %s%s%s\n", pad('0. výchozí stav', 26), pad('funguje', 16), 'SPADNE');
printf("    %s%s%s\n", pad('1. expand', 26), pad('funguje', 16), 'funguje');
printf("    %s%s%s\n", pad('2. backfill', 26), pad('funguje', 16), 'funguje');
printf("    %s%s%s\n", pad('3. migrate', 26), pad('funguje', 16), 'funguje');
printf("    %s%s%s\n\n", pad('4. contract', 26), pad('SPADNE', 16), 'funguje');

echo "    Fáze 1 až 3 jsou bezpečné a dá se v nich zůstat. Nasazení\n";
echo "    se v nich může kdykoli vrátit zpět, protože obě verze\n";
echo "    aplikace vedle sebe fungují.\n\n";

echo "    Sato o poslední fázi: „If the contract phase is not executed\n";
echo "    you might end up in a worse state than you started.\"\n";
