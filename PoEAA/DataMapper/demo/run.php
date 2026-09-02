<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Data Mapper.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Domain/Money.php';
require __DIR__ . '/Domain/OrderStatus.php';
require __DIR__ . '/Domain/OrderItem.php';
require __DIR__ . '/Domain/Order.php';
require __DIR__ . '/Mapper/LegacyOrderMapper.php';
require __DIR__ . '/Mapper/ModernOrderMapper.php';
require __DIR__ . '/ActiveRecord/OrderRecord.php';

use ActiveRecord\OrderRecord;
use Domain\Money;
use Domain\Order;
use Domain\OrderItem;
use Mapper\LegacyOrderMapper;
use Mapper\ModernOrderMapper;

$db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
LegacyOrderMapper::createSchema($db);
ModernOrderMapper::createSchema($db);
OrderRecord::createSchema($db);

/** Doménový objekt — vytvořený bez jakéhokoli kontaktu s databází. */
function sampleOrder(): Order
{
    return Order::place(
        'OBJ-4711',
        'pekarna@example.com',
        [
            new OrderItem('MON-27', 'Monitor 27"', Money::fromCents(799000), 2),
            new OrderItem('KLA-01', 'Klávesnice', Money::fromCents(129000), 1),
        ],
        new DateTimeImmutable('2026-09-01 10:00:00'),
    );
}

echo "=== Data Mapper ===\n\n";

// --- 1. Co v doméně není --------------------------------------------------

echo "1. Doménový objekt neví o databázi nic\n";

/**
 * Vrátí jen SKUTEČNÝ KÓD — bez komentářů a docbloků.
 *
 * Hledat v celém souboru by nefungovalo: komentáře v Order.php
 * o databázi mluví (vysvětlují, že tam není), a `DateTimeImmutable`
 * v sobě obsahuje řetězec „table“.
 */
function codeOnly(string $file): string
{
    $code = '';

    foreach (token_get_all((string) file_get_contents($file)) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], strict: true)) {
            continue;
        }

        $code .= is_array($token) ? $token[1] : $token;
    }

    return $code;
}

$source = codeOnly(__DIR__ . '/Domain/Order.php');
$forbidden = ['PDO', 'SELECT', 'INSERT', 'UPDATE', 'save', 'Doctrine', 'Repository', 'Connection'];

printf("\n    Order.php, jen kód bez komentářů. Hledám stopy po persistenci:\n\n");

foreach ($forbidden as $needle) {
    printf("        %s %s\n", mb_str_pad($needle, 12), str_contains($source, $needle) ? '← NALEZENO' : 'není');
}

$order = sampleOrder();
printf("\n    A přesto funguje: %s, %s, %s\n", $order->number, $order->total()->format(), $order->status()->value);
echo "    Vzniklo to bez databáze, bez konfigurace, bez ORM.\n";

// --- 2. Nesoulad objektu a tabulky ----------------------------------------

echo "\n2. Co všechno musí mapper srovnat\n\n";

$legacy = new LegacyOrderMapper($db);
$legacy->insert($order);

$row = $db->query("SELECT * FROM objednavky WHERE cislo = 'OBJ-4711'")->fetch(PDO::FETCH_ASSOC);

printf("    %s %s\n", mb_str_pad('v doméně', 32), 've starém schématu');
printf("    %s %s\n", mb_str_pad('─────────', 32), '──────────────────');
printf("    %s castka_kc = '%s' + mena = '%s'\n", mb_str_pad("Money(1727000, 'CZK')", 32), $row['castka_kc'], $row['mena']);
printf("    %s stav_kod = %s\n", mb_str_pad("OrderStatus::New", 32), $row['stav_kod']);
printf("    %s dt_vytvoreni = '%s'\n", mb_str_pad("DateTimeImmutable", 32), $row['dt_vytvoreni']);
printf("    %s tabulka objednavky_polozky\n", mb_str_pad("list<OrderItem>", 32));
printf("    %s sloupec castka_kc (uložený!)\n", mb_str_pad("total() — počítá se z položek", 32));

echo "\n    Poslední řádek je nejzajímavější: schéma drží denormalizovaný\n";
echo "    součet, doména ne. Mapper ho při zápisu dopočítá, při čtení\n";
echo "    ignoruje — a doména o té nesrovnalosti neví.\n";

// --- 3. Tentýž objekt, jiné schéma ----------------------------------------

echo "\n3. Změna schématu se domény nedotkne\n";

$modern = new ModernOrderMapper($db);
$modern->insert(sampleOrder());

$fromLegacy = $legacy->find('OBJ-4711');
$fromModern = $modern->find('OBJ-4711');

printf("\n    ze starého schématu:  %s · %s · %s · %s\n",
    $fromLegacy->number, $fromLegacy->total()->format(), $fromLegacy->status()->value, $fromLegacy->placedAt->format('j. n. Y'));
printf("    z nového schématu:    %s · %s · %s · %s\n",
    $fromModern->number, $fromModern->total()->format(), $fromModern->status()->value, $fromModern->placedAt->format('j. n. Y'));

$columnsOf = static function (PDO $db, string $table): array {
    return array_map(
        static fn (array $c): string => $c['name'],
        $db->query(sprintf('PRAGMA table_info(%s)', $table))->fetchAll(PDO::FETCH_ASSOC),
    );
};

$legacyColumns = [...$columnsOf($db, 'objednavky'), ...$columnsOf($db, 'objednavky_polozky')];
$modernColumns = [...$columnsOf($db, 'orders'), ...$columnsOf($db, 'order_items')];
$shared = array_values(array_intersect($legacyColumns, $modernColumns));

printf("\n    sloupců ve starém schématu:  %d  (%s…)\n", count($legacyColumns), implode(', ', array_slice($legacyColumns, 0, 3)));
printf("    sloupců v novém schématu:    %d  (%s…)\n", count($modernColumns), implode(', ', array_slice($modernColumns, 0, 3)));
printf("    společných:                  %d  %s\n", count($shared), $shared === [] ? '← ani jeden' : implode(', ', $shared));
echo "\n    Řádků změněných v Domain/Order.php:  0\n";

// --- 4. Active Record pro srovnání ----------------------------------------

echo "\n4. Active Record — co získáš a co ztratíš\n";

$record = new OrderRecord($db);
$record->number = 'AR-001';
$record->customerEmail = 'pekarna@example.com';
$record->totalCents = 1727000;
$record->save();
$record->markPaid();

printf("\n    \$record->save() — kratší kód, hotovo na jednom řádku\n");
printf("    stav v databázi: %s\n", OrderRecord::find($db, 'AR-001')->status);

echo "\n    Co se tím ale spojilo:\n";
printf("        %s Data Mapper   Active Record\n", mb_str_pad('', 34));
printf("        %s ne            ANO\n", mb_str_pad('objekt zná jméno tabulky', 34));
printf("        %s ne            ANO\n", mb_str_pad('objekt drží spojení do DB', 34));
printf("        %s ne            ANO\n", mb_str_pad('změna schématu mění doménu', 34));
printf("        %s ne            ANO\n", mb_str_pad('test pravidla potřebuje DB', 34));
printf("        %s ANO           ne\n", mb_str_pad('dvě třídy místo jedné', 34));

// --- 5. Rekonstrukce -------------------------------------------------------

echo "\n5. Jak se mapper dostane objektu dovnitř\n";

echo "\n    Order::place() vyžaduje aspoň jednu položku:\n";

try {
    Order::place('OBJ-X', 'a@b.cz', [], new DateTimeImmutable());
} catch (DomainException $e) {
    printf("        %s\n", $e->getMessage());
}

echo "\n    Jenže mapper načítá data, která už jednou platná byla —\n";
echo "    a dnešní pravidlo tehdy nemuselo existovat. Proto má doména\n";
echo "    druhou továrnu: Order::reconstitute(), která zakládací\n";
echo "    pravidla neprochází.\n";
echo "\n    Doctrine tenhle problém řeší jinak: obchází konstruktor\n";
echo "    úplně a nastaví vlastnosti reflexí. Výsledek je stejný —\n";
echo "    jen to není vidět.\n";
