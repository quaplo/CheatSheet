<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Optimistic Offline Lock.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Domain/Order.php';
require __DIR__ . '/Persistence/ConcurrentModification.php';
require __DIR__ . '/Persistence/NaiveOrderStore.php';
require __DIR__ . '/Persistence/VersionedOrderStore.php';

use Domain\Order;
use Persistence\ConcurrentModification;
use Persistence\NaiveOrderStore;
use Persistence\VersionedOrderStore;

$db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
NaiveOrderStore::createSchema($db);
VersionedOrderStore::createSchema($db);

echo "=== Optimistic Offline Lock ===\n\n";

// --- 1. Ztracená aktualizace ----------------------------------------------

echo "1. Ztracená aktualizace — bez zámku\n\n";

$naive = new NaiveOrderStore($db);
$naive->insert(Order::place('OBJ-001'));

echo "    Dva lidé si otevřou tutéž objednávku:\n";
$anna = $naive->get('OBJ-001');      // Anna načte
$bedrich = $naive->get('OBJ-001');   // Bedřich načte totéž, o vteřinu později

echo "        Anna    načetla  → mění PRIORITU\n";
echo "        Bedřich načetl   → mění POZNÁMKU\n\n";

$anna->changePriority('urgentní');
$naive->save($anna);
printf("    Anna uloží:    priorita = '%s'\n", $naive->get('OBJ-001')->priority());

$bedrich->changeNote('volat před doručením');
$naive->save($bedrich);

$result = $naive->get('OBJ-001');
printf("    Bedřich uloží: poznámka = '%s', priorita = '%s'\n", $result->note(), $result->priority());

echo "\n    Annina změna je pryč. Bedřich ji nepřepsal schválně — uložil\n";
echo "    prostě to, co měl načtené. A nikdo se nic nedozvěděl:\n";
echo "    žádná chyba, žádné varování, nic v logu.\n";

// --- 2. Se zámkem ----------------------------------------------------------

echo "\n2. Totéž s optimistickým zámkem\n\n";

$versioned = new VersionedOrderStore($db);
$versioned->insert(Order::place('OBJ-002'));

$anna = $versioned->get('OBJ-002');
$bedrich = $versioned->get('OBJ-002');

printf("    Anna    načetla verzi %d\n", $anna->version);
printf("    Bedřich načetl  verzi %d\n\n", $bedrich->version);

$anna->changePriority('urgentní');
$versioned->save($anna);
printf("    Anna uloží    → v databázi je verze %d\n", $versioned->currentVersion('OBJ-002'));

$bedrich->changeNote('volat před doručením');

try {
    $versioned->save($bedrich);
} catch (ConcurrentModification $e) {
    printf("    Bedřich uloží → %s\n", $e->getMessage());
}

$state = $versioned->get('OBJ-002');
printf("\n    stav: priorita = '%s', poznámka = '%s'\n", $state->priority(), $state->note());
echo "    Anniny změny přežily. Bedřich se dozvěděl, že se nepovedlo.\n";

// --- 3. Co s konfliktem udělat --------------------------------------------

echo "\n3. Konflikt se pozná — ale co dál?\n\n";
echo "    Detekce je půlka práce. Druhá půlka je rozhodnout,\n";
echo "    co se má stát. Tady automatické opakování nad čerstvým stavem:\n\n";

$attempt = 0;

while (true) {
    $attempt++;
    $fresh = $versioned->get('OBJ-002');
    $fresh->changeNote('volat před doručením');

    try {
        $versioned->save($fresh);
        printf("        pokus %d: uloženo (verze %d)\n", $attempt, $versioned->currentVersion('OBJ-002'));

        break;
    } catch (ConcurrentModification) {
        printf("        pokus %d: konflikt, načítám znovu\n", $attempt);
    }
}

$state = $versioned->get('OBJ-002');
printf("\n    výsledek: priorita = '%s', poznámka = '%s'\n", $state->priority(), $state->note());
echo "    Obě změny se zachovaly, protože se ta druhá udělala znovu\n";
echo "    nad aktuálním stavem — ne nad zastaralou kopií.\n";

// --- 4. Kdy opakovat a kdy ne ---------------------------------------------

echo "\n4. Opakovat automaticky se ale nesmí vždycky\n\n";
printf("    %s  opakovat?\n", mb_str_pad('operace', 40));
printf("    %s  %s\n", mb_str_pad('přičti 100 bodů', 40), 'ANO — výsledek nezávisí na tom, co bylo');
printf("    %s  %s\n", mb_str_pad('změň stav na odeslaná', 40), 'ANO — cíl je stejný');
printf("    %s  %s\n", mb_str_pad('nastav cenu na 500 (viděl jsem 400)', 40), 'NE — uživatel rozhodoval podle starých dat');
printf("    %s  %s\n", mb_str_pad('schval slevu, kterou jsem viděl', 40), 'NE — musí se podívat znovu');

echo "\n    Pravidlo: opakuj, když operace nezávisí na tom, co uživatel\n";
echo "    viděl. Když rozhodoval podle dat, která už neplatí, musí\n";
echo "    dostat konflikt na oči.\n";

// --- 5. Verze patří na kořen agregátu -------------------------------------

echo "\n5. Kde má verze být\n\n";
echo "    Ne na každé entitě — na KOŘENI AGREGÁTU.\n\n";
echo "        Order            version   ← tady\n";
echo "          └ OrderItem              ← ne tady\n";
echo "          └ OrderItem              ← ani tady\n\n";
echo "    Změna položky zvýší verzi objednávky. Tím je chráněný celý\n";
echo "    agregát včetně svých invariantů — kdyby měla verzi každá\n";
echo "    položka, mohli by dva lidé měnit dvě různé položky a součet\n";
echo "    by přesáhl limit, aniž by kdokoli dostal konflikt.\n\n";
echo "    Fowler tomu říká Coarse-Grained Lock: jeden zámek na celek,\n";
echo "    ne na každou část.\n";
