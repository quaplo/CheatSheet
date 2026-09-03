<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Service Composition.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Contexts/SalesContext.php';
require __DIR__ . '/Contexts/BillingContext.php';
require __DIR__ . '/Contexts/ShippingContext.php';
require __DIR__ . '/Composition/OrderDetailView.php';
require __DIR__ . '/Composition/OrderDetailComposer.php';
require __DIR__ . '/Composition/PlaceOrderComposer.php';

use Composition\OrderDetailComposer;
use Composition\PlaceOrderComposer;
use Contexts\BillingContext;
use Contexts\SalesContext;
use Contexts\ShippingContext;

function money(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

echo "=== Service Composition ===\n\n";

$sales = new SalesContext();
$billing = new BillingContext();
$shipping = new ShippingContext();

// --- 1. Čtecí kompozice ----------------------------------------------------

echo "1. Čtecí kompozice — jeden pohled ze tří kontextů\n";

$composer = new OrderDetailComposer($sales, $billing, $shipping);
$view = $composer->compose('OBJ-4711');

printf("\n    Sales     %s, %s, %s\n", $view->order['orderId'], $view->order['customer'], money($view->order['totalInCents']));
printf("    Billing   %s, splatnost %s, %s\n", $view->invoice['invoiceNumber'], $view->invoice['dueDate'], $view->invoice['isPaid'] ? 'zaplaceno' : 'nezaplaceno');
printf("    Shipping  %s (%s), doručení %s\n", $view->tracking['trackingNumber'], $view->tracking['carrier'], $view->tracking['estimatedAt']);

echo "\n    Kompozice volá jen veřejné use-case těch kontextů.\n";
echo "    Žádné repository, žádné doménové objekty, žádná jejich databáze.\n";

// --- 2. Výpadek u čtení degraduje, neshodí --------------------------------

echo "\n2. Když jeden kontext mlčí\n";

$shipping->isDown = true;
$view = $composer->compose('OBJ-4711');

printf("\n    Sales     %s   ← povinná část, je\n", $view->order['orderId']);
printf("    Billing   %s   ← je\n", $view->invoice['invoiceNumber']);
printf("    Shipping  —          ← chybí\n");
printf("    nedostupné: %s, pohled úplný: %s\n", implode(', ', $view->unavailable), $view->isComplete() ? 'ano' : 'ne');

echo "\n    Obrazovka se ukáže. Chybí na ní sledování zásilky, a to je\n";
echo "    přijatelné — protože se NIC NEMĚNILO. To je celá výhoda čtení.\n";

$shipping->isDown = false;

// --- 3. Zápisová kompozice a částečné selhání -----------------------------

echo "\n3. Zápisová kompozice — a proč existuje Saga\n";

$shipping->isDown = true;
$writer = new PlaceOrderComposer($sales, $billing, $shipping);

echo "\n    placeOrder():\n";
printf("        1. Sales::placeOrder()      \n");
printf("        2. Billing::issueInvoice()  \n");
printf("        3. Shipping::scheduleDelivery()\n\n");

try {
    $writer->place('CUST-4711', 620000);
} catch (RuntimeException $e) {
    printf("        %s\n", $e->getMessage());
}

printf("\n    Stav po selhání:\n");
printf("        objednávka založena:   ano\n");
printf("        faktura vystavena:     ano (%s)\n", implode(', ', $billing->issuedInvoices));
printf("        zásilka naplánována:   NE\n");

echo "\n    Kroky 1 a 2 proběhly a nikdo je nevrátí. Zákazník má fakturu\n";
echo "    za zboží, které nikdo neodeslal — a systém o tom neví.\n";
echo "\n    Tohle kompozice neumí vyřešit. Na to je potřeba Saga\n";
echo "    s kompenzačními akcemi.\n";

$shipping->isDown = false;

// --- 4. Cena za synchronní volání -----------------------------------------

echo "\n4. Dostupnost se násobí\n\n";

$single = 0.999;

printf("    %s %14s %18s\n", mb_str_pad('kontextů', 10), 'dostupnost', 'výpadek měsíčně');

foreach ([1, 3, 5, 8] as $count) {
    $composite = $single ** $count;
    $minutes = (1 - $composite) * 30 * 24 * 60;

    printf("    %s %13.2f %% %14.0f min\n", mb_str_pad((string) $count, 10), $composite * 100, $minutes);
}

echo "\n    Každý kontext má 99,9 %. Když je zavoláš synchronně za sebou,\n";
echo "    jejich nedostupnosti se sčítají — u osmi je z 44 minut měsíčně\n";
echo "    skoro šest hodin.\n";
echo "\n    Tomuhle se říká časová vazba: kompozice je dole vždy, když je\n";
echo "    dole kterýkoli z volaných, i kdyby s tou operací logicky\n";
echo "    nesouvisel.\n";

// --- 5. Kolik volání ------------------------------------------------------

echo "\n5. Kolik to stálo volání\n";
printf("    Sales %d, Billing %d, Shipping %d\n", $sales->calls, $billing->calls, $shipping->calls);
echo "\n    U čtecí kompozice se dá většina volání pustit paralelně —\n";
echo "    latence pak není součet, ale maximum. U zápisu obvykle ne,\n";
echo "    protože kroky na sebe navazují.\n";
