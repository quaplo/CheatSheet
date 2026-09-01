<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Ports & Adapters.
 *
 * Spuštění:  php run.php
 *
 * Tohle demo má na rozdíl od ostatních složky a jmenné prostory — struktura
 * je tady totiž součástí patternu. Podívej se na adresáře: Core/ nesmí
 * odkazovat na nic z Adapter/, zatímco opačně to platit musí.
 */

// --- jádro ---
require __DIR__ . '/Core/Domain/Order.php';
require __DIR__ . '/Core/Port/Driven/PaymentResult.php';
require __DIR__ . '/Core/Port/Driven/PaymentGateway.php';
require __DIR__ . '/Core/Port/Driven/OrderRepository.php';
require __DIR__ . '/Core/Port/Driving/PlaceOrderCommand.php';
require __DIR__ . '/Core/Port/Driving/PaymentDeclined.php';
require __DIR__ . '/Core/Port/Driving/PlaceOrder.php';
require __DIR__ . '/Core/Application/PlaceOrderHandler.php';

// --- adaptéry ---
require __DIR__ . '/Adapter/Driven/Persistence/InMemoryOrderRepository.php';
require __DIR__ . '/Adapter/Driven/Persistence/JsonFileOrderRepository.php';
require __DIR__ . '/Adapter/Driven/Payment/AlwaysApprovingPaymentGateway.php';
require __DIR__ . '/Adapter/Driven/Payment/LimitedPaymentGateway.php';
require __DIR__ . '/Adapter/Driving/Cli/CliPlaceOrderController.php';

use Adapter\Driven\Payment\AlwaysApprovingPaymentGateway;
use Adapter\Driven\Payment\LimitedPaymentGateway;
use Adapter\Driven\Persistence\InMemoryOrderRepository;
use Adapter\Driven\Persistence\JsonFileOrderRepository;
use Adapter\Driving\Cli\CliPlaceOrderController;
use Core\Application\PlaceOrderHandler;
use Core\Port\Driving\PaymentDeclined;
use Core\Port\Driving\PlaceOrderCommand;

echo "=== Ports & Adapters ===\n\n";

// --- 1. Sestavení pro test: paměť + brána, která vždy schválí -------------

echo "1. Konfigurace pro test — bez databáze, bez sítě\n";

$testRepository = new InMemoryOrderRepository();
$testHandler = new PlaceOrderHandler($testRepository, new AlwaysApprovingPaymentGateway());

$number = $testHandler->place(new PlaceOrderCommand('alice@example.com', 129000));

printf("        založeno: %s\n", $number);
printf("        objednávek v úložišti: %d, zaplaceno: %s\n\n",
    count($testRepository->all()),
    $testRepository->findByNumber($number)?->isPaid() ? 'ano' : 'ne',
);

// --- 2. Táž třída jádra, jiné adaptéry ------------------------------------

echo "2. Konfigurace „na ostro“ — soubor + brána s limitem\n";

$storage = sys_get_temp_dir() . '/ports-and-adapters-demo.json';
@unlink($storage);

$liveRepository = new JsonFileOrderRepository($storage);
$liveHandler = new PlaceOrderHandler($liveRepository, new LimitedPaymentGateway(limitInCents: 200000));

printf("        úložiště: %s\n", $storage);
printf("        PlaceOrderHandler je tatáž třída jako výše — jen dostal jiné adaptéry.\n\n");

// --- 3. Řídicí adaptér: podnět zvenčí -------------------------------------

echo "3. Řídicí adaptér (CLI) nad tímtéž jádrem\n";

$cli = new CliPlaceOrderController($liveHandler);

$cli->run(['bob@example.com', '1290']);
$cli->run(['carol@example.com', '3400']);   // nad limit karty
$cli->run(['dave@example.com']);            // špatné volání adaptéru

// --- 4. Co skončilo v úložišti --------------------------------------------

echo "\n4. Stav úložiště\n";

foreach ($liveRepository->all() as $order) {
    printf(
        "        %s  %s  %s Kč  ref=%s\n",
        $order->number,
        mb_str_pad($order->customerEmail, 20),
        mb_str_pad(number_format($order->totalInCents / 100, 0, ',', ' '), 6, ' ', STR_PAD_LEFT),
        $order->paymentReference ?? '—',
    );
}

// --- 5. Jádro jde volat i přímo, bez adaptéru -----------------------------

echo "\n5. Volání jádra přímo — přesně tohle dělá unit test\n";

try {
    $testHandler->place(new PlaceOrderCommand('eve@example.com', -100));
} catch (InvalidArgumentException $e) {
    printf("        doména se ubránila: %s\n", $e->getMessage());
}

try {
    (new PlaceOrderHandler(new InMemoryOrderRepository(), new LimitedPaymentGateway(50000)))
        ->place(new PlaceOrderCommand('eve@example.com', 99000));
} catch (PaymentDeclined $e) {
    printf("        selhání v pojmech jádra: %s\n", $e->getMessage());
}

@unlink($storage);

echo "\nJádro se ani jednou nezměnilo. Měnily se jen adaptéry okolo něj.\n";
