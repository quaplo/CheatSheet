<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu State.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/OrderState.php';
require __DIR__ . '/IllegalTransition.php';
require __DIR__ . '/NewOrder.php';
require __DIR__ . '/PaidOrder.php';
require __DIR__ . '/ShippedOrder.php';
require __DIR__ . '/DeliveredOrder.php';
require __DIR__ . '/CancelledOrder.php';
require __DIR__ . '/Order.php';
require __DIR__ . '/OrderStatus.php';

echo "=== State ===\n\n";

// --- 1. Průchod životním cyklem -------------------------------------------

echo "1. Šťastná cesta\n";

$order = Order::place('OBJ-001');
printf("    %s\n", $order->status());

foreach (['pay', 'ship', 'deliver'] as $operation) {
    $order = $order->{$operation}();
    printf("    → %s\n", $order->status());
}

// --- 2. Zakázaná operace ---------------------------------------------------

echo "\n2. Zakázaná operace se pozná hned a řekne proč\n";

try {
    $order->cancel();
} catch (IllegalTransition $e) {
    printf("    %s\n", $e->getMessage());
}

try {
    Order::place('OBJ-002')->ship();
} catch (IllegalTransition $e) {
    printf("    %s\n", $e->getMessage());
}

// --- 3. Introspekce: co teď jde? ------------------------------------------

echo "\n3. Co jde v kterém stavu\n";
echo "   (seznam se nemůže rozejít s chováním — čte se z toho,\n";
echo "    které metody stav skutečně přepsal)\n\n";

foreach (['nová', 'zaplacená', 'odeslaná', 'doručená', 'zrušená'] as $name) {
    $state = OrderState::fromName($name);

    printf(
        "    %s %s\n",
        mb_str_pad($state->name(), 12),
        implode(', ', $state->allowedOperations()) ?: '— koncový stav',
    );
}

// --- 4. Stav nese data, ne jen nálepku ------------------------------------

echo "\n4. Stav může nést i data\n";

$cancelledBeforePayment = Order::place('OBJ-003')->cancel();
$cancelledAfterPayment = Order::place('OBJ-004')->pay()->cancel();

foreach ([$cancelledBeforePayment, $cancelledAfterPayment] as $cancelled) {
    /** @var CancelledOrder $state */
    $state = $cancelled->state;

    printf(
        "    %s  %s  vratka: %s\n",
        $cancelled->number,
        mb_str_pad($cancelled->status(), 10),
        $state->refundRequired ? 'ANO' : 'ne',
    );
}

echo "    ↑ oba jsou „zrušená“, ale nesou jinou informaci\n";
echo "      Tohle je hranice, za kterou enum nestačí.\n";

// --- 5. Uložení a načtení --------------------------------------------------

echo "\n5. Persistence\n";

$stored = $order->status();
printf("    v databázi je uloženo jen: „%s“\n", $stored);

$loaded = Order::reconstitute($order->number, $stored);
printf("    po načtení jde: %s\n", implode(', ', $loaded->state->allowedOperations()) ?: '— koncový stav');

// --- 6. Odlehčená varianta: enum ------------------------------------------

echo "\n6. Odlehčená varianta — týž automat jako enum\n";

$status = OrderStatus::New;
printf("    %s", $status->value);

foreach ([OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered] as $target) {
    $status = $status->transitionTo($target);
    printf(" → %s", $status->value);
}

echo "\n";

try {
    $status->transitionTo(OrderStatus::Cancelled);
} catch (LogicException $e) {
    printf("    %s\n", $e->getMessage());
}

echo "\n    Jeden soubor místo sedmi. Dokud je stav jen nálepka, stačí to.\n";
