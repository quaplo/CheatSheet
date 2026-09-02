<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Decorator.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/ProductRepository.php';
require __DIR__ . '/SqliteProductRepository.php';
require __DIR__ . '/CachingProductRepository.php';
require __DIR__ . '/LoggingProductRepository.php';
require __DIR__ . '/TimingProductRepository.php';

$db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE products (sku TEXT PRIMARY KEY, name TEXT NOT NULL)');
$db->exec("INSERT INTO products VALUES ('MON-27', 'Monitor 27\"'), ('KLA-01', 'Klávesnice')");

/**
 * Klientský kód. Zná JEN rozhraní.
 *
 * Tahle funkce se v celém demu nezmění — ať dostane holé repository,
 * nebo trojnásobně obalené.
 */
function lookup(ProductRepository $repository): void
{
    foreach (['MON-27', 'KLA-01', 'MON-27', 'MON-27'] as $sku) {
        $repository->find($sku);
    }
}

echo "=== Decorator ===\n\n";

// --- 1. Bez dekorátoru ----------------------------------------------------

echo "1. Holé repository\n";

$base = new SqliteProductRepository($db);
lookup($base);

printf("    4 hledání → %d dotazů do databáze\n", $base->queries);

// --- 2. Přidání chování bez zásahu do původní třídy -----------------------

echo "\n2. Přidáme cache — aniž bychom sáhli do SqliteProductRepository\n";

$base = new SqliteProductRepository($db);
$cached = new CachingProductRepository($base);
lookup($cached);

printf("    4 hledání → %d dotazů, %d zásahů do cache, %d minutí\n",
    $base->queries, $cached->hits, $cached->misses);

echo "\n    Funkce lookup() se nezměnila. SqliteProductRepository taky ne.\n";
echo "    Přibyla jen jedna třída, která obaluje.\n";

// --- 3. Stohování ----------------------------------------------------------

echo "\n3. Dekorátory jdou skládat na sebe\n";

$base = new SqliteProductRepository($db);
$stack = new TimingProductRepository(
    new LoggingProductRepository(
        new CachingProductRepository($base),
    ),
);

lookup($stack);

printf("\n    měření → záznam → cache → databáze\n");
printf("        zaznamenaných volání: %d\n", count($stack->durations));
printf("        dotazů do databáze:   %d\n", $base->queries);

// --- 4. Na pořadí záleží ---------------------------------------------------

echo "\n4. Pořadí obalení mění chování\n\n";

$baseA = new SqliteProductRepository($db);
$cacheA = new CachingProductRepository($baseA);
$logA = new LoggingProductRepository($cacheA);       // log VNĚ cache
lookup($logA);

$baseB = new SqliteProductRepository($db);
$logB = new LoggingProductRepository($baseB);
$cacheB = new CachingProductRepository($logB);       // log UVNITŘ cache
lookup($cacheB);

printf("    %s %s %s\n", mb_str_pad('pořadí', 26), mb_str_pad('zapsáno do logu', 17), 'dotazů do DB');
printf("    %s %s %d\n", mb_str_pad('log → cache → DB', 26), mb_str_pad((string) count($logA->log), 17), $baseA->queries);
printf("    %s %s %d\n", mb_str_pad('cache → log → DB', 26), mb_str_pad((string) count($logB->log), 17), $baseB->queries);

echo "\n    Když je log vně cache, zaznamená se KAŽDÝ dotaz aplikace.\n";
echo "    Když je uvnitř, zaznamenají se jen ty, které cache pustila dál.\n";
echo "    Obojí je legitimní — jen to musíš chtít vědomě.\n";

// --- 5. Proč ne dědičnost --------------------------------------------------

echo "\n5. Kolik tříd by stála dědičnost\n\n";

$features = ['cache', 'log', 'měření'];
$combinations = 2 ** count($features);

printf("    vlastností: %d\n", count($features));
printf("    dědičnost:  %d podtříd pro všechny kombinace\n", $combinations);
printf("    dekorátory: %d třídy, kombinace se skládají za běhu\n", count($features));

echo "\n    S každou další vlastností se počet podtříd ZDVOJNÁSOBÍ,\n";
echo "    zatímco dekorátorů přibude jeden. U šesti vlastností:\n";
printf("        dědičnost:  %d podtříd\n", 2 ** 6);
printf("        dekorátory: %d tříd\n", 6);

echo "\n    A hlavně: kombinaci si vybírá volající, ne autor knihovny.\n";
