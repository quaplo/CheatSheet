<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Aggregate.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Domain/OrderId.php';
require __DIR__ . '/Domain/CustomerId.php';
require __DIR__ . '/Domain/OrderItem.php';
require __DIR__ . '/Domain/Order.php';
require __DIR__ . '/Broken/OrderItemRepository.php';

use Broken\OrderItemRepository;
use Domain\CustomerId;
use Domain\Order;
use Domain\OrderId;
use Domain\OrderItem;

function money(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

echo "=== Aggregate ===\n\n";

// --- 1. Kořen je jediná cesta dovnitř -------------------------------------

echo "1. Vše jde přes kořen\n";

$order = Order::place(
    OrderId::generate(),
    CustomerId::fromString('CUST-4711'),
    approvedLimitInCents: 5000000,
);

$order->addItem('KLA-01', 'Klávesnice', 129000, 2);
$order->addItem('MON-27', 'Monitor 27"', 799000, 3);
$order->addItem('KAB-HD', 'Kabel HDMI', 29000, 5);

printf("    %s pro zákazníka %s\n", $order->id->value, $order->customerId->value);

foreach ($order->itemSummary() as $item) {
    printf("        %s  %s %2d ks  %12s\n", $item['sku'], mb_str_pad($item['name'], 14), $item['quantity'], money($item['total']));
}

printf("    celkem %s, do limitu zbývá %s\n", money($order->total()), money($order->remainingLimit()));

// --- 2. Invariant celku, který položka sama neuhlídá ----------------------

echo "\n2. Invariant platí pro CELEK\n";

printf("    zkusíme přidat 4 monitory za %s\n", money(799000 * 4));

try {
    $order->addItem('MON-32', 'Monitor 32"', 799000, 4);
} catch (DomainException $e) {
    printf("    %s\n", $e->getMessage());
}

echo "\n    Ta položka je sama o sobě naprosto v pořádku. Neplatný je až\n";
echo "    součet — a to nevidí žádná položka, jen kořen.\n";

// --- 3. Změna, která by invariant porušila, se vrátí zpět -----------------

echo "\n3. Změna se vrátí, když by celek porušila\n";

printf("    monitorů teď: %d, celkem %s\n", $order->itemSummary()[1]['quantity'], money($order->total()));

try {
    $order->changeQuantity('MON-27', 10);
} catch (DomainException $e) {
    printf("    changeQuantity('MON-27', 10): %s\n", $e->getMessage());
}

printf("    po neúspěšné změně: %d monitorů, celkem %s   ← beze změny\n",
    $order->itemSummary()[1]['quantity'], money($order->total()));

// --- 4. Odkaz na cizí agregát jen identitou -------------------------------

echo "\n4. Cizí agregát jen přes identitu\n";
printf("    Order drží: CustomerId('%s')\n", $order->customerId->value);
echo "    Order NEDRŽÍ: objekt Customer\n";
echo "\n    Kdyby držela objekt, načetla by se s ním půlka databáze —\n";
echo "    a hlavně by vznikla otázka, kdo ho smí měnit.\n";

// --- 5. Co se stane, když se kořen obejde ---------------------------------

echo "\n5. Co udělá repository pro vnitřní entitu\n";

$loose = new OrderItem('MON-27', 'Monitor 27"', 799000, 3);
$broken = new OrderItemRepository();
$broken->remember('MON-27', $loose);

printf("    před:  3 ks × %s = %s\n", money(799000), money($loose->total()));

$broken->updateQuantity('MON-27', 10);

printf("    po:   10 ks × %s = %s\n", money(799000), money($loose->total()));
echo "\n    Limit? Stav objednávky? Počet položek? Nikdo se nezeptal —\n";
echo "    kořen o té změně vůbec neví. Agregát se právě rozpadl.\n";

// --- 6. Hranice platí i pro stav ------------------------------------------

echo "\n6. Hranice drží i po odeslání\n";

$order->ship();
printf("    stav: %s\n", $order->status());

try {
    $order->addItem('KAB-XX', 'Kabel navíc', 10000, 1);
} catch (DomainException $e) {
    printf("    %s\n", $e->getMessage());
}

echo "\n    Jedno místo, které to hlídá. Kdyby existovalo víc cest dovnitř,\n";
echo "    muselo by to hlídat každé z nich — a jedno by se zapomnělo.\n";
