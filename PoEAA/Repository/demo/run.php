<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Repository.
 *
 * Spuštění:  php run.php
 *
 * V reálném projektu by soubory byly rozdělené podle vrstev:
 *
 *     Domain/          OrderId, Order, OrderNotFound, OrderRepository (rozhraní)
 *     Infrastructure/  InMemoryOrderRepository, SqliteOrderRepository
 *
 * Tady jsou vedle sebe, aby šlo demo spustit bez autoloadu — ale to
 * rozdělení je podstatné, viz README.
 */

require __DIR__ . '/OrderId.php';
require __DIR__ . '/Order.php';
require __DIR__ . '/OrderNotFound.php';
require __DIR__ . '/OrderRepository.php';
require __DIR__ . '/InMemoryOrderRepository.php';
require __DIR__ . '/SqliteOrderRepository.php';

/**
 * Aplikační kód. Nezná ani jednu implementaci — zná jen rozhraní.
 *
 * Tahle funkce je celé demo. Spustí se dvakrát, pokaždé nad úplně jinou
 * technologií, a ani o tom neví.
 */
function exercise(OrderRepository $orders): void
{
    $now = new DateTimeImmutable('2026-09-01 12:00:00');

    // 1. Identita vzniká v aplikaci, ne v databázi.
    $id = $orders->nextIdentity();
    printf("        identita před uložením:  %s\n", $id->value);

    $order = Order::place($id, 'alice@example.com', 129000, $now->modify('-10 days'));
    $orders->save($order);

    // 2. Čtení zpátky — dostaneš agregát, ne řádek.
    $loaded = $orders->get($id);
    printf("        načteno:                 %s, %s Kč\n",
        $loaded->customerEmail,
        number_format($loaded->totalInCents / 100, 0, ',', ' '),
    );

    // 3. Další objednávky pro doménový dotaz.
    $orders->save(Order::place($orders->nextIdentity(), 'bob@example.com', 45000, $now->modify('-20 days')));
    $orders->save(Order::place($orders->nextIdentity(), 'carol@example.com', 890000, $now->modify('-1 day')));
    $orders->save(Order::place($orders->nextIdentity(), 'dave@example.com', 210000, $now->modify('-30 days'))->markPaid());

    // 4. Doménový dotaz — pojmenovaný záměrem, ne filtrem.
    $stale = $orders->unpaidPlacedBefore($now->modify('-7 days'));
    printf("        nezaplacené starší 7 dní: %d\n", count($stale));

    foreach ($stale as $candidate) {
        printf("            · %s  %s\n", $candidate->customerEmail, $candidate->placedAt->format('j. n. Y'));
    }

    // 5. Agregace patří do úložiště, ne do PHP.
    printf("        nezaplacených celkem:    %d\n", $orders->countUnpaid());

    // 6. Změna agregátu se ukládá týmž save().
    $orders->save($loaded->markPaid());
    printf("        po zaplacení zbývá:      %d\n", $orders->countUnpaid());

    // 7. Odebrání a chybějící agregát.
    $orders->remove($id);

    try {
        $orders->get($id);
    } catch (OrderNotFound $e) {
        printf("        %s\n", $e->getMessage());
    }
}

echo "=== Repository ===\n\n";

echo "1. Implementace v paměti (to, co běží v testech)\n";
exercise(new InMemoryOrderRepository());

echo "\n2. Implementace nad SQL — tentýž kód, jiná technologie\n";
exercise(new SqliteOrderRepository(new PDO('sqlite::memory:', options: [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
])));

echo "\nFunkce exercise() se mezi během 1 a 2 nezměnila ani o písmeno.\n";
echo "Nezná SQL, nezná pole, nezná sloupce — zná jen OrderRepository.\n";
