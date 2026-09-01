<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Saga.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Contexts/StockContext.php';
require __DIR__ . '/Contexts/PaymentContext.php';
require __DIR__ . '/Contexts/ShippingContext.php';
require __DIR__ . '/Saga/SagaState.php';
require __DIR__ . '/Saga/SagaStep.php';
require __DIR__ . '/Saga/SagaOutcome.php';
require __DIR__ . '/Saga/SagaLog.php';
require __DIR__ . '/Saga/SagaRecovery.php';
require __DIR__ . '/Saga/OrderFulfillmentSaga.php';
require __DIR__ . '/Steps/ReserveStock.php';
require __DIR__ . '/Steps/ChargePayment.php';
require __DIR__ . '/Steps/ScheduleShipping.php';

use Contexts\PaymentContext;
use Contexts\ShippingContext;
use Contexts\StockContext;
use Saga\OrderFulfillmentSaga;
use Saga\SagaLog;
use Saga\SagaRecovery;
use Saga\SagaState;
use Steps\ChargePayment;
use Steps\ReserveStock;
use Steps\ScheduleShipping;

function money(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

function newSaga(StockContext $stock, PaymentContext $payments, ShippingContext $shipping): OrderFulfillmentSaga
{
    return new OrderFulfillmentSaga([
        new ReserveStock($stock),
        new ChargePayment($payments),
        new ScheduleShipping($shipping),   // pivotní — nevratný, proto poslední
    ]);
}

echo "=== Saga ===\n\n";

// --- 1. Šťastná cesta ------------------------------------------------------

echo "1. Všechny kroky projdou\n";

$stock = new StockContext();
$payments = new PaymentContext();
$shipping = new ShippingContext();

$state = new SagaState('OBJ-001', 'MON-27', 2, 1598000);
$outcome = newSaga($stock, $payments, $shipping)->run($state);

printf("\n    kroky:  %s\n", implode(' → ', $state->completedSteps));
printf("    stav:   %s\n", $state->status);
printf("    saldo:  %s\n", money($payments->balanceFor('OBJ-001')));

// --- 2. Selhání uprostřed → kompenzace pozpátku ---------------------------

echo "\n2. Selže platba (krok 2 ze 3)\n";

$stock = new StockContext();
$payments = new PaymentContext();
$shipping = new ShippingContext();
$payments->failNext = true;

$state = new SagaState('OBJ-002', 'KLA-01', 1, 129000);
$outcome = newSaga($stock, $payments, $shipping)->run($state);

printf("\n    selhal krok:   %s (%s)\n", $outcome->failedStep, $outcome->reason);
printf("    kompenzováno:  %s\n", implode(', ', $outcome->compensated) ?: '—');
printf("    stav ságy:     %s\n", $state->status);
printf("    sklad:         %s\n", implode(' · ', $stock->log));
printf("    rezervací:     %d\n", count($stock->reserved));

// --- 3. Kompenzace NENÍ rollback ------------------------------------------

echo "\n3. Selže doprava (krok 3 ze 3) — a tady je ta pointa\n";

$stock = new StockContext();
$payments = new PaymentContext();
$shipping = new ShippingContext();
$shipping->failNext = true;

$state = new SagaState('OBJ-003', 'MON-27', 1, 799000);
$outcome = newSaga($stock, $payments, $shipping)->run($state);

printf("\n    selhal krok:   %s\n", $outcome->failedStep);
printf("    kompenzováno:  %s   ← POZPÁTKU\n", implode(' → ', $outcome->compensated));

echo "\n    Účetní kniha po kompenzaci:\n";

foreach ($payments->ledger as $entry) {
    printf("        %s %14s  %s\n", mb_str_pad($entry['type'], 10), money($entry['amount']), $entry['id']);
}

printf("        %s %14s\n", mb_str_pad('SALDO', 10), money($payments->balanceFor('OBJ-003')));

echo "\n    Saldo je nula, ale ZÁZNAMY ZŮSTALY OBA. Platba se nesmazala —\n";
echo "    přidal se dobropis. To je rozdíl mezi kompenzací a rollbackem:\n";
echo "    rollback předstírá, že se nic nestalo, kompenzace přiznává,\n";
echo "    že se stalo obojí.\n";

// --- 4. Idempotence --------------------------------------------------------

echo "\n4. Kompenzace se může spustit dvakrát\n";

$before = count($payments->ledger);
(new ChargePayment($payments))->compensate($state);
(new ReserveStock($stock))->compensate($state);

printf("\n    záznamů v knize před opakováním: %d\n", $before);
printf("    po opakování kompenzace:         %d   ← beze změny\n", count($payments->ledger));
printf("    sklad: %s\n", end($stock->log));

echo "\n    Doručení „aspoň jednou“ znamená, že kompenzace přijde i podruhé.\n";
echo "    Bez idempotence by zákazník dostal dva dobropisy.\n";

// --- 5. Pivotní krok -------------------------------------------------------

echo "\n5. Za pivotním krokem už zpět nejde\n";

$stock = new StockContext();
$payments = new PaymentContext();
$shipping = new ShippingContext();

$sagaWithStepAfterPivot = new OrderFulfillmentSaga([
    new ReserveStock($stock),
    new ScheduleShipping($shipping),        // pivot — zásilka odjela
    new ChargePayment($payments),           // …a teď selže platba
]);

$payments->failNext = true;
$state = new SagaState('OBJ-004', 'KAB-HD', 3, 87000);
$outcome = $sagaWithStepAfterPivot->run($state);

printf("\n    selhal krok:  %s\n", $outcome->failedStep);
printf("    kompenzace:   %s\n", $outcome->compensated ?: 'ŽÁDNÁ — zásilka už odjela');
printf("    stav ságy:    %s\n", $state->status);
printf("    zásilek:      %d\n", count($shipping->shipments));

echo "\n    Proto se nevratné kroky dávají NAKONEC. Tady byl pivot uprostřed\n";
echo "    a sága skončila zaseknutá — dál se dá jít jen dopředu (reklamace,\n";
echo "    ruční zásah), ne zpátky.\n";

// --- 6. Co saga NEDÁ -------------------------------------------------------

echo "\n6. Co ságou nezískáš\n\n";
echo "    Transakce v jedné databázi:      A · C · I · D\n";
echo "    Sága přes víc kontextů:          A · C ·   · D     ← chybí IZOLACE\n";
echo "\n    Mezi kroky vidí ostatní procesy mezistav: rezervace existuje,\n";
echo "    platba proběhla, zásilka ne. Není to chyba implementace —\n";
echo "    je to cena za to, že nemáš distribuovanou transakci.\n";
echo "\n    Řeší se to sémantickými zámky (rezervace = zámek se smyslem)\n";
echo "    a tím, že se s mezistavem počítá v UI i v pravidlech.\n";

// ==========================================================================
// 7. Synchronní kompenzace stačí — dokud proces doběhne
// ==========================================================================

echo "\n\n7. Co synchronní kompenzace neuhlídá\n";

echo "\n    Všechno výše bylo SYNCHRONNÍ — žádné fronty, přímá volání.\n";
echo "    A fungovalo to. Jenže veškerá informace o rozdělané práci\n";
echo "    žila jen v paměti běžícího procesu.\n";

$stock = new StockContext();
$payments = new PaymentContext();
$shipping = new ShippingContext();

/** Krok, který proces zabije — deploy, OOM, timeout, spadlý kontejner. */
$crashingStep = new class implements \Saga\SagaStep {
    public function name(): string { return 'naplánování dopravy'; }
    public function execute(SagaState $state): void { throw new \Error('Proces zabit (deploy uprostřed operace).'); }
    public function compensate(SagaState $state): void { }
    public function isPivot(): bool { return false; }
};

$state = new SagaState('OBJ-005', 'MON-27', 1, 799000);

echo "\n    BEZ uloženého stavu:\n";

try {
    (new OrderFulfillmentSaga([new ReserveStock($stock), new ChargePayment($payments), $crashingStep]))->run($state);
} catch (Error $e) {
    printf("        %s\n", $e->getMessage());
}

printf("        rezervací ve skladu:  %d   ← osiřelá\n", count($stock->reserved));
printf("        saldo plateb:         %s   ← peníze strženy\n", money($payments->balanceFor('OBJ-005')));
echo "        kompenzace:           ŽÁDNÁ — kód, který o nich věděl, je pryč\n";

// --- 8. Uložený stav + obnova ---------------------------------------------

echo "\n8. Totéž s uloženým stavem\n";

$stock = new StockContext();
$payments = new PaymentContext();
$shipping = new ShippingContext();
$log = new SagaLog();

$steps = [new ReserveStock($stock), new ChargePayment($payments), $crashingStep];
$state = new SagaState('OBJ-006', 'MON-27', 1, 799000);

try {
    (new OrderFulfillmentSaga($steps, $log))->run($state);
} catch (Error $e) {
    printf("\n        %s\n", $e->getMessage());
}

printf("        v databázi zůstalo: %s, hotové kroky: %s\n",
    $log->all()[0]->status, implode(', ', $log->all()[0]->completedSteps));

echo "\n    …a o pět minut později doběhne obnovovací worker:\n";

$recovered = (new SagaRecovery($steps, $log))->recover();

printf("        uklizeno ság:         %s\n", implode(', ', $recovered));
printf("        rezervací ve skladu:  %d   ← uvolněno\n", count($stock->reserved));
printf("        saldo plateb:         %s   ← dobropis vystaven\n", money($payments->balanceFor('OBJ-006')));

echo "\n    Rozdíl mezi 7 a 8 není synchronní × asynchronní.\n";
echo "    Je to ULOŽENÝ STAV — a ten potřebuješ v obou případech.\n";
