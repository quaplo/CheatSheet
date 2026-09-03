<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Domain Event.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Domain/DomainEvent.php';
require __DIR__ . '/Domain/RecordsEvents.php';
require __DIR__ . '/Domain/OrderPlaced.php';
require __DIR__ . '/Domain/OrderShipped.php';
require __DIR__ . '/Domain/Order.php';
require __DIR__ . '/Application/EventDispatcher.php';
require __DIR__ . '/Application/OrderStore.php';
require __DIR__ . '/Application/PlaceOrderHandler.php';
require __DIR__ . '/Handlers/SendConfirmationEmail.php';
require __DIR__ . '/Handlers/ReserveStock.php';
require __DIR__ . '/Handlers/UpdateSalesStats.php';
require __DIR__ . '/Integration/OrderPlacedV1.php';

use Application\EventDispatcher;
use Application\OrderStore;
use Application\PlaceOrderHandler;
use Domain\Order;
use Domain\OrderPlaced;
use Handlers\ReserveStock;
use Handlers\SendConfirmationEmail;
use Handlers\UpdateSalesStats;
use Integration\OrderPlacedV1;

$now = new DateTimeImmutable('2026-09-01 10:00:00');

echo "=== Domain Event ===\n\n";

// --- 1. Agregát zaznamenává, nepublikuje ----------------------------------

echo "1. Agregát událost zaznamená, nerozešle\n";

$order = Order::place('OBJ-001', 'alice@example.com', 129000, [['sku' => 'KLA-01', 'quantity' => 2]], $now);

$recorded = $order->releaseEvents();
printf("    po Order::place() zaznamenáno: %d událost (%s)\n", count($recorded), $recorded[0]::class);
printf("    po druhém releaseEvents():     %d   ← vybírá se jen jednou\n", count($order->releaseEvents()));

echo "\n    Objednávka nezná mailer ani sklad. Jen konstatuje, co se stalo.\n";

// --- 2. Publikace po commitu ----------------------------------------------

echo "\n2. Publikace až po úspěšném uložení\n";

$dispatcher = new EventDispatcher();
$email = new SendConfirmationEmail();
$stock = new ReserveStock();
$stats = new UpdateSalesStats();

$dispatcher->listen(OrderPlaced::class, $email);
$dispatcher->listen(OrderPlaced::class, $stock);
$dispatcher->listen(OrderPlaced::class, $stats);

$store = new OrderStore();
$useCase = new PlaceOrderHandler($store, $dispatcher);

echo "\n        PlaceOrderHandler::place('OBJ-002')\n";
$useCase->place('OBJ-002', 'bob@example.com', 210000, [['sku' => 'MON-27', 'quantity' => 1]], $now);

printf("\n    Use-case má dvě závislosti, reakce tři. A neví o nich.\n");

// --- 3. Co udělá rollback --------------------------------------------------

echo "\n3. Když transakce spadne\n";

echo "\n    SPRÁVNĚ (publikace po commitu):\n";
printf("        e-mailů před pokusem: %d\n", count($email->sent));
echo "        PlaceOrderHandler::place() s prázdnými položkami\n";

try {
    $useCase->place('OBJ-003', 'carol@example.com', 50000, [], $now);
} catch (DomainException $e) {
    printf("        výjimka: %s\n", $e->getMessage());
}

printf("        e-mailů po pokusu:    %d   ← žádný navíc\n", count($email->sent));
printf("        uložených objednávek: %d\n", $store->count());

echo "\n    ŠPATNĚ (publikace uvnitř transakce):\n";

$eagerEmail = new SendConfirmationEmail();
$eagerDispatcher = new EventDispatcher();
$eagerDispatcher->listen(OrderPlaced::class, $eagerEmail);

try {
    $doomed = Order::place('OBJ-004', 'dave@example.com', 90000, [['sku' => 'X', 'quantity' => 1]], $now);
    $eagerDispatcher->dispatchAll($doomed->releaseEvents());   // ← rozeslání PŘED uložením

    throw new RuntimeException('Uložení selhalo (např. deadlock).');
} catch (RuntimeException $e) {
    printf("        výjimka: %s\n", $e->getMessage());
}

printf("        odeslaných e-mailů:   %d   ← zákazník má potvrzení objednávky, která neexistuje\n", count($eagerEmail->sent));

// --- 4. Nová reakce = nový handler ----------------------------------------

echo "\n4. Přidání reakce se use-case nedotkne\n";

$auditLog = [];
$dispatcher->listen(OrderPlaced::class, static function (OrderPlaced $event) use (&$auditLog): void {
    $auditLog[] = sprintf('%s objednána v %s', $event->orderId, $event->occurredAt()->format('H:i'));
    printf("            → audit: %s\n", end($auditLog));
});

echo "\n        PlaceOrderHandler::place('OBJ-005')\n";
$useCase->place('OBJ-005', 'eve@example.com', 45000, [['sku' => 'KAB-HD', 'quantity' => 3]], $now);

echo "\n    PlaceOrderHandler se nezměnil ani o písmeno.\n";

// --- 5. Doménová vs integrační událost ------------------------------------

echo "\n5. Ven z kontextu jde jiná událost\n";

$internal = new OrderPlaced('OBJ-006', 'frank@example.com', 320000,
    [['sku' => 'A', 'quantity' => 1], ['sku' => 'B', 'quantity' => 2]], $now);

echo "\n    doménová (uvnitř):\n";
printf("        e-mail zákazníka, položky se SKU, náš tvar\n");

echo "\n    integrační (ven):\n";
$message = json_encode(OrderPlacedV1::fromDomainEvent($internal)->toMessage(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

foreach (explode("\n", (string) $message) as $line) {
    echo '        ' . $line . "\n";
}

echo "\n    Má verzi, nenese e-mail zákazníka a nezmění se, když se změní\n";
echo "    náš vnitřní model. Kdybychom ven poslali doménovou událost,\n";
echo "    stal by se z našeho modelu veřejné API.\n";
