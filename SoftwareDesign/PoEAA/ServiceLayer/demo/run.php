<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Service Layer / use-case.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Domain/OrderId.php';
require __DIR__ . '/Domain/CreditLimitExceeded.php';
require __DIR__ . '/Domain/Order.php';
require __DIR__ . '/Domain/OrderRepository.php';
require __DIR__ . '/Domain/InMemoryOrderRepository.php';
require __DIR__ . '/Application/CustomerCredit.php';
require __DIR__ . '/Application/EventPublisher.php';
require __DIR__ . '/Application/PlaceOrder.php';
require __DIR__ . '/Application/PlaceOrderHandler.php';
require __DIR__ . '/Application/CancelOrder.php';
require __DIR__ . '/Application/CancelOrderHandler.php';
require __DIR__ . '/Before/OrderService.php';
require __DIR__ . '/Leaky/LooseOrder.php';
require __DIR__ . '/Leaky/PlaceOrderHandler.php';
require __DIR__ . '/Application/Query/OrderSummary.php';
require __DIR__ . '/Application/Query/OrderSummaryQuery.php';
require __DIR__ . '/Application/Query/OrderReadSource.php';
require __DIR__ . '/Application/Query/OrderSummaryHandler.php';
require __DIR__ . '/Application/Query/CachedOrderSummaryHandler.php';
require __DIR__ . '/Leaky/ImportOrdersHandler.php';

use Application\CancelOrder;
use Application\CancelOrderHandler;
use Application\CustomerCredit;
use Application\EventPublisher;
use Application\PlaceOrder;
use Application\PlaceOrderHandler;
use Application\Query\CachedOrderSummaryHandler;
use Application\Query\OrderReadSource;
use Application\Query\OrderSummary;
use Application\Query\OrderSummaryHandler;
use Application\Query\OrderSummaryQuery;
use Domain\CreditLimitExceeded;
use Domain\InMemoryOrderRepository;

function money(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

/** @param class-string $class */
function dependencyCount(string $class): int
{
    return (new ReflectionClass($class))->getConstructor()?->getNumberOfParameters() ?? 0;
}

/** @param class-string $class */
function publicMethodCount(string $class): int
{
    return count(array_filter(
        (new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC),
        static fn (ReflectionMethod $m): bool => $m->getName() !== '__construct',
    ));
}

$credit = new class implements CustomerCredit {
    public function limitFor(string $customerId): int
    {
        return match ($customerId) {
            'CUST-VIP' => 5000000,
            default => 200000,
        };
    }
};

echo "=== Service Layer / use-case ===\n\n";

// --- 1. Jedna služba na všechno vs. třída na use-case ---------------------

echo "1. Kolik toho každá třída potřebuje\n\n";

printf("    %s  závislostí: %d, veřejných metod: %d\n",
    mb_str_pad('Before\OrderService', 32),
    dependencyCount(Before\OrderService::class),
    publicMethodCount(Before\OrderService::class),
);
printf("    %s  závislostí: %d, veřejných metod: %d\n",
    mb_str_pad('Application\PlaceOrderHandler', 32),
    dependencyCount(PlaceOrderHandler::class),
    publicMethodCount(PlaceOrderHandler::class),
);
printf("    %s  závislostí: %d, veřejných metod: %d\n",
    mb_str_pad('Application\CancelOrderHandler', 32),
    dependencyCount(CancelOrderHandler::class),
    publicMethodCount(CancelOrderHandler::class),
);

echo "\n    CancelOrderHandler nepotřebuje úvěrový limit, tak si o něj neřekne.\n";
echo "    Ve velké službě by ho dostal taky — konstruktor je jeden pro všechny.\n";

// --- 2. Use-case orchestruje, nerozhoduje ---------------------------------

echo "\n2. Co use-case dělá\n";

$orders = new InMemoryOrderRepository();
$events = new EventPublisher();
$placeOrder = new PlaceOrderHandler($orders, $credit, $events);

$orderId = $placeOrder->handle(new PlaceOrder('CUST-VIP', 1200000));

printf("\n    PlaceOrder('CUST-VIP', %s) → %s\n", money(1200000), $orderId);
printf("    publikováno: %s\n", implode(', ', $events->published));
printf("    ven jde identita, ne agregát: %s\n", get_debug_type($orderId));

echo "\n    Pět kroků, ani jeden z nich není byznysové rozhodnutí.\n";

// --- 3. Doménová chyba projde přes aplikační vrstvu -----------------------

echo "\n3. Rozhoduje doména, ne use-case\n";

try {
    $placeOrder->handle(new PlaceOrder('CUST-BEZNY', 900000));
} catch (CreditLimitExceeded $e) {
    printf("    %s\n", $e->getMessage());
}

echo "    Ta výjimka přišla z Order::place(), ne z handleru.\n";

// --- 4. A teď co udělá pravidlo v aplikační vrstvě ------------------------

echo "\n4. Když pravidlo prosákne do use-case\n";

$limits = ['CUST-BEZNY' => 200000];
$leakyPlace = new Leaky\PlaceOrderHandler($limits);

echo "\n    přes PlaceOrderHandler (pravidlo tam je):\n";

try {
    $leakyPlace->handle('CUST-BEZNY', 900000);
} catch (DomainException $e) {
    printf("        %s   ← zachyceno\n", $e->getMessage());
}

echo "\n    přes ImportOrdersHandler (jiná cesta, o pravidle neví):\n";

$import = new Leaky\ImportOrdersHandler();
$import->handle([
    ['customerId' => 'CUST-BEZNY', 'total' => 900000],
    ['customerId' => 'CUST-BEZNY', 'total' => 4500000],
]);

foreach ($import->saved as $bad) {
    printf("        %s  zákazník %s  %s   ← limit %s, PROŠLO\n",
        $bad->id, $bad->customerId, money($bad->totalInCents), money(200000));
}

echo "\n    Import nikdo nenapsal špatně. Autor jen nemohl vědět o pravidle,\n";
echo "    které bydlí v cizím use-case místo v doméně.\n";

// --- 5. Kontrolní otázka ---------------------------------------------------

echo "\n5. Kam co patří\n\n";
echo "    Zeptej se: platilo by to pravidlo i tehdy, kdyby aplikace neměla\n";
echo "    HTTP, frontu ani databázi?\n\n";
echo "        ano  → doména       „nad limit se objednat nedá“\n";
echo "        ne   → use-case     „načti limit, otevři transakci, publikuj událost“\n";

// --- 6. Druhý use-case -----------------------------------------------------

echo "\n6. Nová operace = nová třída\n";

$cancel = new CancelOrderHandler($orders, $events);
$cancel->handle(new CancelOrder($orderId, 'zákazník si to rozmyslel'));

printf("    stav objednávky %s: %s\n", $orderId, $orders->all()[0]->status());
printf("    publikováno celkem: %s\n", implode(', ', $events->published));

echo "\n    PlaceOrderHandler se kvůli tomu nezměnil.\n";

// --- 7. Dotazovací strana --------------------------------------------------

echo "\n7. A co dotazy?\n";

$reads = new class implements OrderReadSource {
    public int $calls = 0;

    /** @return list<OrderSummary> */
    public function summariesFor(string $customerId, int $limit): array
    {
        $this->calls++;

        return [
            new OrderSummary('OBJ-A1', 129000, 'nová'),
            new OrderSummary('OBJ-B2', 45000, 'odeslaná'),
        ];
    }
};

$summaries = new OrderSummaryHandler($reads);

foreach ($summaries->handle(new OrderSummaryQuery('CUST-VIP', limit: 10)) as $row) {
    printf("    %s  %10s  %s\n", $row->orderId, money($row->totalInCents), $row->status);
}

echo "\n    Co dotazovací handler NEDĚLÁ oproti příkazovému:\n\n";
printf("        %s  příkaz   dotaz\n", mb_str_pad('', 22));
printf("        %s    ano      NE\n", mb_str_pad('transakce', 22));
printf("        %s    ano      NE\n", mb_str_pad('publikuje události', 22));
printf("        %s    ano      NE\n", mb_str_pad('prochází agregátem', 22));
printf("        %s  identita   DTO\n", mb_str_pad('vrací', 22));
printf("        %s     NE      ano\n", mb_str_pad('lze cachovat', 22));

echo "\n    Proto bývá dotazovací handler tenký — často jen předá volání dál.\n";

// --- 8. Kdy se ten tenký handler vyplatí ----------------------------------

echo "\n8. Kdy má tenký dotazovací handler smysl\n";

$cached = new CachedOrderSummaryHandler($summaries);
$query = new OrderSummaryQuery('CUST-VIP', limit: 10);

for ($i = 0; $i < 4; $i++) {
    $cached->handle($query);
}

printf("\n    4× tentýž dotaz přes dekorátor:\n");
printf("        zásahy do cache: %d, minutí: %d\n", $cached->hits, $cached->misses);
printf("        volání čtecího zdroje celkem: %d\n", $reads->calls);

echo "\n    Tohle je ten důvod: když má dotaz i příkaz stejný tvar\n";
echo '    handle($vstup): $výstup, jde kolem obou obalit cokoli' . "\n";
echo "    průřezového — cache, autorizaci, měření, audit.\n";
echo "\n    Když ale žádný takový důvod nemáš, zavolej čtecí službu\n";
echo "    z controlleru rovnou. Handler jen kvůli symetrii s příkazy\n";
echo "    je stovka jednořádkových tříd bez obsahu.\n";
