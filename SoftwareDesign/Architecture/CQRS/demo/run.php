<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu CQRS.
 *
 * Spuštění:  php run.php
 *
 * Ukazuje stupeň 3 ze škály v README: jedna databáze, jedno schéma,
 * ale dvě oddělené cesty k datům. Žádná eventuální konzistence,
 * žádné projekce, žádný Event Sourcing.
 */

require __DIR__ . '/CountingPdo.php';
require __DIR__ . '/Schema.php';
require __DIR__ . '/OrderItem.php';
require __DIR__ . '/Order.php';
require __DIR__ . '/OrderRepository.php';
require __DIR__ . '/SqliteOrderRepository.php';
require __DIR__ . '/PlaceOrder.php';
require __DIR__ . '/PlaceOrderHandler.php';
require __DIR__ . '/OrderListItem.php';
require __DIR__ . '/OrderListQuery.php';

function money(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

$db = new CountingPdo('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
Schema::create($db);

$repository = new SqliteOrderRepository($db);
$handler = new PlaceOrderHandler($repository);
$list = new OrderListQuery($db);

echo "=== CQRS ===\n\n";

// --- 1. Zápisová strana ----------------------------------------------------

echo "1. Zápis jde přes doménu\n";

$now = new DateTimeImmutable('2026-09-01 09:00:00');
$products = ['Klávesnice', 'Myš', 'Monitor', 'Kabel', 'Sluchátka'];

for ($i = 1; $i <= 500; $i++) {
    $items = [];

    for ($j = 0; $j < random_int(1, 4); $j++) {
        $items[] = new OrderItem($products[array_rand($products)], random_int(20000, 900000), random_int(1, 3));
    }

    $handler->handle(
        new PlaceOrder(sprintf('zakaznik%03d@example.com', $i), $items),
        $now->modify(sprintf('-%d minutes', $i)),
    );
}

printf("    založeno 500 objednávek přes PlaceOrderHandler\n");

// Doména si pořád hlídá svá pravidla.
try {
    $handler->handle(new PlaceOrder('nikdo@example.com', []), $now);
} catch (InvalidArgumentException $e) {
    printf("    pravidla platí dál: %s\n", $e->getMessage());
}

// --- 2. Totéž dvěma cestami ------------------------------------------------

echo "\n2. Výpis 20 nejnovějších objednávek — dvě cesty\n\n";

// (a) Bez čtecí strany: načti agregáty, seřaď a namapuj v PHP.
$db->reset();
$startedAt = hrtime(true);

$all = $repository->allForComparison();
usort($all, static fn (Order $a, Order $b): int => $b->placedAt <=> $a->placedAt);
$page = array_slice($all, 0, 20);

$viaAggregates = [
    'ms' => (hrtime(true) - $startedAt) / 1e6,
    'queries' => $db->queries,
    'loaded' => count($all),
];

// (b) Čtecí strana: jeden dotaz.
$db->reset();
$startedAt = hrtime(true);

$rows = $list->recent(limit: 20);

$viaQuery = [
    'ms' => (hrtime(true) - $startedAt) / 1e6,
    'queries' => $db->queries,
    'loaded' => count($rows),
];

printf("    %s %8s %10s %10s\n", mb_str_pad('', 20), 'dotazů', 'čas', 'načteno');
printf(
    "    %s %8d %7.1f ms %10d\n",
    mb_str_pad('přes agregáty', 20),
    $viaAggregates['queries'],
    $viaAggregates['ms'],
    $viaAggregates['loaded'],
);
printf(
    "    %s %8d %7.1f ms %10d\n",
    mb_str_pad('přes čtecí model', 20),
    $viaQuery['queries'],
    $viaQuery['ms'],
    $viaQuery['loaded'],
);

printf(
    "\n    Obě cesty vrátí týchž 20 řádků. Jedna kvůli tomu načte %d objednávek\n    a %d× se zeptá databáze, druhá se zeptá %d×.\n",
    $viaAggregates['loaded'],
    $viaAggregates['queries'],
    $viaQuery['queries'],
);

// --- 3. Jak vypadá čtecí model --------------------------------------------

echo "\n3. Čtecí model je tvar obrazovky, ne entita\n\n";

foreach (array_slice($rows, 0, 5) as $row) {
    printf(
        "    %s  %s  %s  %2d ks  %10s\n",
        $row->placedAt,
        mb_str_pad($row->customerEmail, 26),
        mb_str_pad($row->status, 10),
        $row->itemCount,
        money($row->totalInCents),
    );
}

echo "    …\n";
echo "    Plochý řádek se sečtenou cenou a počtem položek. Agregát nic\n";
echo "    takového nemá — a mít nemá, protože to není doménový pojem.\n";

// --- 4. Stránkování a souhrny zdarma --------------------------------------

echo "\n4. Co je na čtecí straně triviální\n";

$db->reset();
$secondPage = $list->recent(limit: 20, offset: 20);
printf("    druhá stránka:  %d řádků, %d dotaz\n", count($secondPage), $db->queries);

$db->reset();
printf("    obrat celkem:   %s, %d dotaz\n", money($list->totalRevenue()), $db->queries);

echo "\n    Přes agregáty by obojí znamenalo načíst všechno do paměti.\n";

// --- 5. Zápisová strana zůstala nedotčená ---------------------------------

echo "\n5. Zápisová strana se nezměnila\n";

$id = $rows[0]->id;
$db->reset();
$aggregate = $repository->get($id);

printf(
    "    načtení agregátu %s: dotazů %d, položek %d, celkem %s\n",
    $aggregate->id,
    $db->queries,
    count($aggregate->items),
    money($aggregate->total()),
);

echo "    Pro změnu jedné objednávky je tohle přesně správně.\n";
echo "    Pro tabulku o dvaceti řádcích to bylo přesně špatně.\n";
