<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Domain Service.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Domain/Currency.php';
require __DIR__ . '/Domain/Money.php';
require __DIR__ . '/Domain/ExchangeRate.php';
require __DIR__ . '/Domain/Rounding.php';
require __DIR__ . '/Domain/ExchangeRateConverter.php';
require __DIR__ . '/Domain/CustomerId.php';
require __DIR__ . '/Domain/Customer.php';
require __DIR__ . '/Domain/TransferRejected.php';
require __DIR__ . '/Domain/TransferReceipt.php';
require __DIR__ . '/Domain/LoyaltyPointsTransfer.php';
require __DIR__ . '/Wrong/PointsUtils.php';
require __DIR__ . '/Wrong/CustomerWithTransfer.php';

use Domain\Currency;
use Domain\Customer;
use Domain\ExchangeRate;
use Domain\ExchangeRateConverter;
use Domain\Money;
use Domain\Rounding;
use Domain\LoyaltyPointsTransfer;
use Domain\TransferRejected;

echo "=== Domain Service ===\n\n";

// ==========================================================================
// A. NESPORNÁ PODOBA: výpočet nad hodnotami
// ==========================================================================

echo "A. Převod měny — o téhle podobě se nikdo nehádá\n";

$converter = new ExchangeRateConverter();
$rate = ExchangeRate::of(Currency::EUR, Currency::CZK, 24.5678);
$price = Money::fromCents(1999, Currency::EUR);

printf("\n    kurz:  %s\n", $rate->describe());
printf("    částka: %s\n\n", $price->format());

foreach ([Rounding::InFavourOfCustomer, Rounding::InFavourOfCompany] as $mode) {
    printf("    %s  %s\n",
        mb_str_pad($mode === Rounding::InFavourOfCustomer ? 've prospěch zákazníka' : 've prospěch firmy', 24),
        $converter->convert($price, $rate, $mode)->format(),
    );
}

echo "\n    Kam to pověsit? Money by muselo znát kurzy, ExchangeRate\n";
echo "    zaokrouhlovací politiku, Currency je enum. Ani jeden z nich\n";
echo "    není vlastníkem — operace je MEZI nimi.\n";
echo "\n    Žádný agregát, žádná změna stavu, žádný cizí kontext.\n";
echo "    Hodnoty dovnitř, hodnota ven.\n";

// ==========================================================================
// B. SPORNÁ PODOBA: operace nad dvěma agregáty
// ==========================================================================

echo "\n\nB. Převod bodů mezi zákazníky — tady se názory rozcházejí\n";

echo "\n1. Jak to funguje\n";

$alice = Customer::register('CUST-A', 'Alice', 2000);
$bob = Customer::register('CUST-B', 'Bob', 300);

$transfer = new LoyaltyPointsTransfer();

printf("\n    před:   Alice %d b, Bob %d b\n", $alice->points(), $bob->points());

$receipt = $transfer->transfer($alice, $bob, 500);

printf("    převod: −%d b, poplatek %d b, připsáno %d b\n", $receipt->debited, $receipt->fee, $receipt->credited);
printf("    po:     Alice %d b, Bob %d b\n", $alice->points(), $bob->points());

echo "\n    Pravidla o dvojici (minimum, poplatek, oba aktivní) jsou v službě.\n";
echo "    Pravidla o jednotlivci si dál hlídá každý agregát sám.\n";

// --- 2. Pravidla o dvojici -------------------------------------------------

echo "\n2. Co služba odmítne\n";

foreach ([
    ['popis' => 'sám sobě', 'from' => $alice, 'to' => $alice, 'points' => 500],
    ['popis' => 'pod minimem', 'from' => $alice, 'to' => $bob, 'points' => 50],
] as $case) {
    try {
        $transfer->transfer($case['from'], $case['to'], $case['points']);
    } catch (TransferRejected $e) {
        printf("    %s %s\n", mb_str_pad($case['popis'] . ':', 14), $e->getMessage());
    }
}

$carol = Customer::register('CUST-C', 'Carol', 5000);
$carol->deactivate();

try {
    $transfer->transfer($carol, $bob, 500);
} catch (TransferRejected $e) {
    printf("    %s %s\n", mb_str_pad('neaktivní:', 14), $e->getMessage());
}

// --- 3. Agregát si dál hlídá své ------------------------------------------

echo "\n3. Služba nepřebírá pravidla agregátů\n";

try {
    $transfer->transfer($bob, $alice, 100000);
} catch (DomainException $e) {
    printf("    %s\n", $e->getMessage());
    printf("    ↑ tahle výjimka přišla z Customer::redeemPoints(), ne ze služby\n");
}

// --- 4. Proč to není metoda na entitě -------------------------------------

echo "\n4. Proč to není metoda na entitě\n";

$a = Wrong\CustomerWithTransfer::register('CUST-A', 'Alice', 2000);
$b = Wrong\CustomerWithTransfer::register('CUST-B', 'Bob', 300);

printf("\n    \$alice->transferTo(\$bob, 500)\n");
$a->transferTo($b, 500);
printf("    Alice %d b, Bob %d b   ← chování je správné\n", $a->points(), $b->points());

echo "\n    Potíže jsou jinde:\n";
echo "        · asymetrie — proč metoda patří zdroji a ne cíli?\n";
echo "        · Alice sáhla do Boba a změnila ho → cizí agregát\n";
echo "        · při uložení se zapisují dva agregáty naráz a nikdo\n";
echo "          neřeší, co když druhý zápis selže\n";

// --- 5. Bezstavová ---------------------------------------------------------

echo "\n5. Doménová služba je bezstavová\n";

$dave = Customer::register('CUST-D', 'Dave', 10000);
$eve = Customer::register('CUST-E', 'Eve', 0);

foreach ([1000, 2000, 3000] as $amount) {
    $r = $transfer->transfer($dave, $eve, $amount);
    printf("    převod %d b → poplatek %d b, připsáno %d b\n", $amount, $r->fee, $r->credited);
}

printf("    Dave %d b, Eve %d b\n", $dave->points(), $eve->points());
echo "\n    Táž instance služby, tři nezávislé převody. Nedrží si nic.\n";

// --- 6. Kontrolní otázky ---------------------------------------------------

echo "\n6. Patří ta operace do doménové služby?\n\n";
echo "    Zeptej se v tomhle pořadí. Doménová služba je až poslední možnost:\n\n";
echo "        1. Patří to jedné ENTITĚ?           → metoda na entitě\n";
echo "        2. Patří to HODNOTĚ?                → metoda na value objectu\n";
echo "        3. Je to jen ANO/NE pravidlo?       → Specification\n";
echo "        4. Je to orchestrace, transakce,\n";
echo "           načítání, události?              → use-case (aplikační vrstva)\n";
echo "        5. Doménová operace nad víc objekty\n";
echo "           bez infrastruktury?              → DOMÉNOVÁ SLUŽBA\n";

// --- 7. Přiznaný spor ------------------------------------------------------

echo "\n\n7. Proč je varianta B sporná\n";

echo "\n    Ta služba mění DVA agregáty. Z toho plyne otázka, kterou\n";
echo "    sama nezodpoví: uloží se oba v jedné transakci?\n";
echo "\n        ano  → porušil jsi „jedna transakce = jeden agregát“\n";
echo "        ne   → co když druhý zápis selže? Body zmizely.\n";
echo "\n    Tři legitimní odpovědi, mezi kterými se rozhoduje podle\n";
echo "    toho, jak daleko od sebe ty agregáty jsou:\n";
echo "\n        1. Doménová služba + obojí v jedné transakci\n";
echo "           Funguje, dokud jsou v jedné databázi a konflikty jsou vzácné.\n";
echo "\n        2. Vlastní agregát PointsTransfer\n";
echo "           Převod se stane věcí, která má identitu, stav a historii.\n";
echo "           Zákazníci se pak upraví reakcí na událost.\n";
echo "\n        3. Use-case + eventuální konzistence\n";
echo "           Když jsou agregáty v různých kontextech nebo službách,\n";
echo "           je tohle jediná poctivá odpověď.\n";
echo "\n    Doménová služba NIKDY nesahá do jiného bounded contextu.\n";
echo "    Tam končí a začíná aplikační vrstva.\n";
