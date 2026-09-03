<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Unit of Work.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Domain/Product.php';
require __DIR__ . '/Persistence/ImmediateProductStore.php';
require __DIR__ . '/Persistence/UnitOfWork.php';

use Domain\Product;
use Persistence\ImmediateProductStore;
use Persistence\UnitOfWork;

function freshDb(): PDO
{
    $db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('CREATE TABLE products (sku TEXT PRIMARY KEY, name TEXT, price INTEGER, stock INTEGER)');
    $db->exec("INSERT INTO products VALUES
        ('MON-27', 'Monitor 27\"', 799000, 12),
        ('KLA-01', 'Klávesnice', 129000, 40),
        ('MYS-01', 'Myš', 45000, 100)");

    return $db;
}

echo "=== Unit of Work ===\n\n";

// --- 1. Bez Unit of Work --------------------------------------------------

echo "1. Bez Unit of Work — ukládá se po každé změně\n";

$db = freshDb();
$store = new ImmediateProductStore($db);

$monitor = new Product('MON-27', 'Monitor 27"', 799000, 12);

$monitor->changePrice(749000);
$store->save($monitor);
$monitor->rename('Monitor 27" Full HD');
$store->save($monitor);
$monitor->reserve(2);
$store->save($monitor);

printf("\n    3 změny jednoho objektu → %d zápisů do databáze\n", $store->writes);
echo "    A volající si musel po každé změně pamatovat, že má uložit.\n";

// --- 2. S Unit of Work ----------------------------------------------------

echo "\n2. S Unit of Work — jeden commit na konci\n";

$db = freshDb();
$uow = new UnitOfWork($db);

$monitor = $uow->find('MON-27');
$monitor->changePrice(749000);
$monitor->rename('Monitor 27" Full HD');
$monitor->reserve(2);

$result = $uow->commit();

printf("\n    3 změny jednoho objektu → %d zápis\n", $uow->writes);
printf("    commit(): %d vloženo, %d aktualizováno, %d beze změny\n",
    $result['inserted'], $result['updated'], $result['unchanged']);
echo "\n    Doména se nezměnila. Product nemá save() ani nic podobného —\n";
echo "    prostě se mění a Unit of Work si toho všimne.\n";

// --- 3. Co se nezměnilo, se nezapisuje ------------------------------------

echo "\n3. Sledování změn: zapisuje se jen to, co se opravdu změnilo\n";

$db = freshDb();
$uow = new UnitOfWork($db);

$uow->find('MON-27')->changePrice(699000);
$uow->find('KLA-01');                          // jen načteno, nic neměněno
$uow->find('MYS-01')->reserve(5);

$result = $uow->commit();

printf("\n    načteny 3 produkty, změněny 2\n");
printf("    zápisů do databáze: %d   (aktualizováno %d, beze změny %d)\n",
    $uow->writes, $result['updated'], $result['unchanged']);

echo "\n    Klávesnice se jen podívala a nezapsala se. Bez sledování\n";
echo "    změn by se přepsala vlastní hodnotou — zbytečný zápis,\n";
echo "    zbytečný zámek, zbytečné zvýšení verze.\n";

// --- 4. Identity map ------------------------------------------------------

echo "\n4. Identity map: týž záznam = tentýž objekt\n";

$db = freshDb();
$uow = new UnitOfWork($db);

$a = $uow->find('MON-27');
$b = $uow->find('MON-27');   // druhé „načtení“

$a->changePrice(555000);

printf("\n    dotazů do databáze: %d\n", $uow->reads);
printf("    \$a === \$b:          %s\n", $a === $b ? 'true' : 'false');
printf("    změna přes \$a je vidět na \$b: %s\n",
    $b->priceInCents() === 555000 ? 'ano' : 'ne');

echo "\n    Bez identity map by existovaly dvě kopie téhož produktu.\n";
echo "    Kdyby každá dostala jinou změnu, jedna by druhou přepsala —\n";
echo "    a nikdo by to nepoznal.\n";

// --- 5. Všechno, nebo nic -------------------------------------------------

echo "\n5. Když operace uprostřed selže\n";

echo "\n    BEZ Unit of Work:\n";
$db = freshDb();
$store = new ImmediateProductStore($db);

$monitor = new Product('MON-27', 'Monitor 27"', 799000, 12);
$monitor->changePrice(1);
$store->save($monitor);                        // ← už je v databázi

try {
    $keyboard = new Product('KLA-01', 'Klávesnice', 129000, 40);
    $keyboard->reserve(999);                   // spadne
    $store->save($keyboard);
} catch (DomainException $e) {
    printf("        výjimka: %s\n", $e->getMessage());
}

$price = $db->query("SELECT price FROM products WHERE sku = 'MON-27'")->fetchColumn();
printf("        cena monitoru v databázi: %s   ← změna zůstala\n", number_format((int) $price / 100, 2, ',', ' '));

echo "\n    S Unit of Work:\n";
$db = freshDb();
$uow = new UnitOfWork($db);

try {
    $uow->find('MON-27')->changePrice(1);
    $uow->find('KLA-01')->reserve(999);        // spadne PŘED commitem
    $uow->commit();
} catch (DomainException $e) {
    printf("        výjimka: %s\n", $e->getMessage());
}

$price = $db->query("SELECT price FROM products WHERE sku = 'MON-27'")->fetchColumn();
printf("        cena monitoru v databázi: %s   ← nic se nezapsalo\n", number_format((int) $price / 100, 2, ',', ' '));

echo "\n    Změny žily jen v paměti, dokud někdo neřekl commit().\n";
echo "    Když se k němu nedošlo, není co vracet.\n";
