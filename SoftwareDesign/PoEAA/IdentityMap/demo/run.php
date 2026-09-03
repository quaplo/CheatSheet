<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Identity Map.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Product.php';
require __DIR__ . '/ProductStorage.php';
require __DIR__ . '/IdentityMap.php';
require __DIR__ . '/ProductRepository.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function seed(): ProductStorage
{
    $storage = new ProductStorage();
    $storage->insert('MON-27', 'Monitor 27"', 799000);
    $storage->insert('KLA-01', 'Klávesnice mechanická', 249000);

    return $storage;
}

function mb(int $bytes): string
{
    return number_format($bytes / 1024 / 1024, 1, ',', ' ') . ' MB';
}

echo "=== Identity Map ===\n\n";

// --- 1. Bez mapy: dvě kopie téhož záznamu ----------------------------------

echo "1. Bez Identity Map — jedna změna tiše zmizí\n\n";

$storage = seed();
$repository = new ProductRepositoryWithoutMap($storage);

// Dvě části jedné operace si nezávisle načtou týž produkt.
$forPricing = $repository->find('MON-27');
$forCatalog = $repository->find('MON-27');

$forPricing->changePrice(749000);
$forCatalog->rename('Monitor 27" Full HD');

$repository->save($forPricing);
$repository->save($forCatalog);

printf("    táž instance?          %s\n", $forPricing === $forCatalog ? 'ano' : 'ne');
printf("    v databázi zůstalo:    %s za %s\n", $storage->peek('MON-27')['name'], formatPrice($storage->peek('MON-27')['price']));
printf("    dotazů:                %d\n\n", $storage->queryCount);

echo "    Sleva se ztratila. Druhý save() zapsal celý objekt,\n";
echo "    který o změně ceny nikdy nevěděl. Nic nespadlo,\n";
echo "    nic se nezalogovalo — jen je v databázi špatná cena.\n\n";

// --- 2. S mapou: jedna instance na záznam ----------------------------------

echo "2. S Identity Map — jeden záznam, jedna instance\n\n";

$storage = seed();
$identityMap = new IdentityMap();
$repository = new ProductRepository($storage, $identityMap);

$forPricing = $repository->find('MON-27');
$forCatalog = $repository->find('MON-27');

$forPricing->changePrice(749000);
$forCatalog->rename('Monitor 27" Full HD');

$repository->save($forPricing);
$repository->save($forCatalog);

printf("    táž instance?          %s\n", $forPricing === $forCatalog ? 'ano' : 'ne');
printf("    v databázi zůstalo:    %s za %s\n", $storage->peek('MON-27')['name'], formatPrice($storage->peek('MON-27')['price']));
printf("    dotazů:                %d   ← druhý find() do databáze nešel\n\n", $storage->queryCount);

echo "    Obě změny jsou tam. Nejsou to dvě kopie, které se\n";
echo "    přepisují — je to jeden objekt, který se změnil dvakrát.\n\n";

// --- 3. Identity Map není cache --------------------------------------------

echo "3. Identity Map není cache\n\n";

$storage = seed();
$identityMap = new IdentityMap();
$repository = new ProductRepository($storage, $identityMap);

$original = $repository->find('KLA-01');

// Cache smí vrátit rovnocennou KOPII — typicky přes serializaci.
$fromCache = unserialize(serialize($original));

// Identity Map musí vrátit TUTÉŽ instanci.
$fromMap = $repository->find('KLA-01');

// Nejdřív porovnání, teprve pak změna — jinak by se obojí mísilo.
$equalCache = $original == $fromCache;
$sameCache = $original === $fromCache;
$equalMap = $original == $fromMap;
$sameMap = $original === $fromMap;

$original->changePrice(199000);

printf("    %s %s %s\n", pad('', 22), pad('z cache', 16), 'z identity map');
printf("    %s %s %s\n", pad('== (rovnocenné)', 22), pad($equalCache ? 'ano' : 'ne', 16), $equalMap ? 'ano' : 'ne');
printf("    %s %s %s\n", pad('=== (táž instance)', 22), pad($sameCache ? 'ano' : 'ne', 16), $sameMap ? 'ano' : 'ne');
printf("    %s %s %s\n\n", pad('vidí pozdější změnu', 22), pad($fromCache->priceInCents() === 199000 ? 'ano' : 'ne', 16), $fromMap->priceInCents() === 199000 ? 'ano' : 'ne');

echo "    Cache řeší RYCHLOST a kopie jí stačí.\n";
echo "    Identity Map řeší IDENTITU a kopie ji rozbije.\n";
echo "    Proto se Identity Map nikdy neukládá do Redisu.\n\n";

// --- 4. Jak dlouho má mapa žít ---------------------------------------------

echo "4. Mapa platí jen pro jednu operaci\n\n";

$storage = seed();
$identityMap = new IdentityMap();
$repository = new ProductRepository($storage, $identityMap);

$product = $repository->find('KLA-01');
printf("    načteno:               %s\n", formatPrice($product->priceInCents()));

// Někdo jiný mezitím změnil cenu přímo v databázi.
$storage->insert('KLA-01', 'Klávesnice mechanická', 179000);

$again = $repository->find('KLA-01');
printf("    v databázi je teď:     %s\n", formatPrice($storage->peek('KLA-01')['price']));
printf("    mapa vrací pořád:      %s   ← zastaralé\n", formatPrice($again->priceInCents()));

$identityMap->clear();
$fresh = $repository->find('KLA-01');
printf("    po clear():            %s\n\n", formatPrice($fresh->priceInCents()));

echo "    Uvnitř jedné operace je to správně — chceš konzistentní\n";
echo "    pohled. Přes hranici operace je to chyba. Proto mapa\n";
echo "    žije jeden request nebo jednu transakci, ne déle.\n\n";

// --- 5. Dávka: mapa roste, dokud ji někdo nevyprázdní ----------------------

echo "5. Dávkové zpracování — proč se v cyklu volá clear()\n\n";

$batchSize = 50_000;

$storage = new ProductStorage();

for ($i = 0; $i < $batchSize; ++$i) {
    $storage->insert(sprintf('SKU-%05d', $i), 'Produkt ' . $i, 100000 + $i);
}

// a) mapa se nikdy nevyprázdní
$identityMap = new IdentityMap();
$repository = new ProductRepository($storage, $identityMap);

$before = memory_get_usage();

for ($i = 0; $i < $batchSize; ++$i) {
    $repository->find(sprintf('SKU-%05d', $i));
}

$withoutClear = memory_get_usage() - $before;
$mapSize = $identityMap->count();

$identityMap->clear();
gc_collect_cycles();

// b) mapa se každých 1 000 položek vyprázdní
$identityMap = new IdentityMap();
$repository = new ProductRepository($storage, $identityMap);

$before = memory_get_usage();

for ($i = 0; $i < $batchSize; ++$i) {
    $repository->find(sprintf('SKU-%05d', $i));

    if ($i % 1000 === 999) {
        $identityMap->clear();
    }
}

$withClear = memory_get_usage() - $before;

printf("    %s %s %s\n", pad('', 26), pad('paměť', 12), 'objektů v mapě');
printf("    %s %s %s\n", pad('bez clear()', 26), pad(mb($withoutClear), 12), number_format($mapSize, 0, ',', ' '));
printf("    %s %s %s\n\n", pad('clear() po 1 000', 26), pad(mb($withClear), 12), number_format($identityMap->count(), 0, ',', ' '));

printf("    PHP %s, %s položek. Jde o řád, ne o absolutní čísla.\n\n", PHP_VERSION, number_format($batchSize, 0, ',', ' '));

echo "    Mapa drží referenci na každý načtený objekt, takže ho\n";
echo "    garbage collector nemůže uklidit. V dlouhém importu to\n";
echo "    není optimalizace, ale rozdíl mezi doběhnutím a pádem.\n";
echo "    Tohle je přesně důvod, proč se v Doctrine dávkách\n";
echo "    volá \$entityManager->clear().\n";
