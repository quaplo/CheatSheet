<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Observer.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/StockObserver.php';
require __DIR__ . '/StockItem.php';
require __DIR__ . '/LowStockAlert.php';
require __DIR__ . '/StockAuditLog.php';
require __DIR__ . '/ReorderSuggestion.php';

echo "=== Observer ===\n\n";

// --- 1. Subjekt nezná své pozorovatele ------------------------------------

echo "1. Sklad nezná nikoho, kdo ho poslouchá\n";

$item = new StockItem('MON-27', 50);

$alert = new LowStockAlert(threshold: 10);
$audit = new StockAuditLog();
$reorder = new ReorderSuggestion();

$item->subscribe($alert);
$item->subscribe($audit);
$item->subscribe($reorder);

printf("\n    přihlášených pozorovatelů: %d\n", $item->observerCount());
echo "    StockItem o nich neví nic — zná jen rozhraní StockObserver.\n";

// --- 2. Jedna změna, tři reakce -------------------------------------------

echo "\n2. Jedna změna, každý si vezme své\n";

foreach ([30, 8, 0] as $quantity) {
    printf("\n        changeQuantity(%d):\n", $quantity);
    $item->changeQuantity($quantity);
}

printf("\n    záznamů v auditu:  %d\n", count($audit->entries));
printf("    upozornění:        %d\n", count($alert->alerts));

echo "\n    Audit zaznamenal všechny tři změny, upozornění jen tu jednu,\n";
echo "    kdy se překročila hranice. Každý pozorovatel si sám vybírá,\n";
echo "    co ho zajímá — subjekt to za něj neřeší.\n";

// --- 3. Přidání a odebrání za běhu ----------------------------------------

echo "\n3. Odběry se dají měnit za běhu\n";

$item->unsubscribe($alert);
$item->changeQuantity(3);

printf("\n    po odhlášení upozornění: %d pozorovatelé, %d záznamů v auditu\n",
    $item->observerCount(), count($audit->entries));

echo "    Ani odhlášení se subjektu nedotklo — jen ubyl z pole.\n";

// --- 4. Změna, která není změnou ------------------------------------------

echo "\n4. Co se nezměnilo, se neoznamuje\n";

$before = count($audit->entries);
$item->changeQuantity(3);   // stejná hodnota

printf("\n    changeQuantity(3) na hodnotu, která už platí\n");
printf("    záznamů v auditu: %d → %d   ← beze změny\n", $before, count($audit->entries));

echo "\n    Bez téhle kontroly by pozorovatelé dostali oznámení o ničem\n";
echo "    a audit by se plnil prázdnými řádky.\n";

// --- 5. Když pozorovatel selže --------------------------------------------

echo "\n5. Nejnepříjemnější vlastnost synchronního Observeru\n";

$item = new StockItem('KLA-01', 20);
$audit = new StockAuditLog();
$reorder = new ReorderSuggestion();

$item->subscribe($reorder);   // vadný je PRVNÍ
$item->subscribe($audit);

$reorder->failNext = true;

try {
    $item->changeQuantity(5);
} catch (RuntimeException $e) {
    printf("\n        výjimka z pozorovatele: %s\n", $e->getMessage());
}

printf("        množství na skladě:  %d   ← změna proběhla\n", $item->quantity());
printf("        záznamů v auditu:    %d   ← druhý pozorovatel se ke slovu nedostal\n", count($audit->entries));

echo "\n    Vadný pozorovatel shodil i ten druhý, a hlavně vyhodil výjimku\n";
echo "    do operace, která s doplňováním zásob nemá nic společného.\n";
echo "\n    Řešení je chytat výjimky kolem KAŽDÉHO pozorovatele zvlášť —\n";
echo "    a je to rozhodnutí, které se musí udělat vědomě.\n";

// --- 6. Observer vs Domain Event ------------------------------------------

echo "\n6. Kdy Observer a kdy doménová událost\n\n";
printf("    %s %s %s\n", mb_str_pad('', 24), mb_str_pad('Observer', 22), 'Domain Event');
printf("    %s %s %s\n", mb_str_pad('kdo oznamuje', 24), mb_str_pad('objekt sám', 22), 'aplikační vrstva');
printf("    %s %s %s\n", mb_str_pad('kdy', 24), mb_str_pad('OKAMŽITĚ při změně', 22), 'až po commitu');
printf("    %s %s %s\n", mb_str_pad('rozsah', 24), mb_str_pad('jeden proces', 22), 'i mimo proces');
printf("    %s %s %s\n", mb_str_pad('přežije rollback', 24), mb_str_pad('NE — už se stalo', 22), 'ano, nepublikuje se');
printf("    %s %s %s\n", mb_str_pad('vhodné pro', 24), mb_str_pad('UI, cache, ladění', 22), 'doménové reakce');

echo "\n    Observer oznamuje HNED. Když se transakce vrátí zpět,\n";
echo "    reakce už proběhly — a to je přesně důvod, proč doménové\n";
echo "    události vznikly.\n";
