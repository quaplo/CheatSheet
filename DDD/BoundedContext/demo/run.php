<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Bounded Context.
 *
 * Spuštění:  php run.php
 *
 * Demo má složky a jmenné prostory, protože hranice kontextu je celý
 * pattern — a ta musí být vidět ve struktuře, ne jen v hlavě.
 */

require __DIR__ . '/Shared/CustomerId.php';
require __DIR__ . '/Sales/Customer.php';
require __DIR__ . '/Billing/Customer.php';
require __DIR__ . '/Support/Customer.php';
require __DIR__ . '/Billing/PayerFromWonDeal.php';
require __DIR__ . '/Before/Customer.php';

use Shared\CustomerId;

function money(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

echo "=== Bounded Context ===\n\n";

// --- 1. Jedna firma, tři modely -------------------------------------------

$id = CustomerId::fromString('CUST-4711');

$inSales = new Sales\Customer(
    id: $id,
    companyName: 'Pekárna Novák s.r.o.',
    contactPerson: 'Jana Nováková',
    dealValueInCents: 1200000,
    probabilityPercent: 60,
    accountManager: 'petr.svoboda',
);

$inBilling = new Billing\Customer(
    id: $id,
    legalName: 'Pekárna Novák s.r.o.',
    vatId: 'CZ27082440',
    billingAddress: 'Dlouhá 12, 110 00 Praha 1',
    paymentTermDays: 14,
    creditLimitInCents: 2400000,
);

$inSupport = new Support\Customer(
    id: $id,
    displayName: 'Pekárna Novák',
    email: 'jana@pekarnanovak.cz',
    supportTier: 'gold',
    openTickets: 2,
);

echo "1. Tatáž firma ve třech kontextech\n";
printf("   identita %s je všude stejná, model není\n\n", $id->value);

printf("    Sales    „zákazník“ = příležitost\n");
printf("             hodnota %s, %d %% → vážená %s, %s\n",
    money($inSales->dealValueInCents),
    $inSales->probabilityPercent,
    money($inSales->weightedValue()),
    $inSales->isWorthPursuing() ? 'stojí za to' : 'nechat být',
);

printf("\n    Billing  „zákazník“ = plátce\n");
printf("             %s, splatnost %d dní, limit %s\n",
    $inBilling->vatId,
    $inBilling->paymentTermDays,
    money($inBilling->creditLimitInCents),
);
printf("             objednávka za %s při %s nezaplacených: %s\n",
    money(500000),
    money(2000000),
    $inBilling->canOrderFor(500000, 2000000) ? 'projde' : 'ZAMÍTNUTO',
);

printf("\n    Support  „zákazník“ = tazatel\n");
printf("             %s, úroveň %s → odpovědět do %d h, otevřených ticketů %d\n",
    $inSupport->email,
    $inSupport->supportTier,
    $inSupport->responseDeadlineHours(),
    $inSupport->openTickets,
);

echo "\n    Žádný z těch tří modelů není neúplný. Každý je úplný pro to,\n";
echo "    co se v jeho kontextu dělá.\n";

// --- 2. Kolik by stál jeden společný model --------------------------------

echo "\n2. Co by z toho bylo, kdyby existoval jeden model\n";

$reflection = new ReflectionClass(Before\Customer::class);
$properties = $reflection->getProperties();

$nullable = array_filter(
    $properties,
    static fn (ReflectionProperty $p): bool => $p->getType()?->allowsNull() ?? false,
);

printf("    Before\\Customer:  %d vlastností, z toho %d nullable\n", count($properties), count($nullable));
printf("    Sales\\Customer:   %d vlastností, 0 nullable\n", count((new ReflectionClass(Sales\Customer::class))->getProperties()));
printf("    Billing\\Customer: %d vlastností, 0 nullable\n", count((new ReflectionClass(Billing\Customer::class))->getProperties()));
printf("    Support\\Customer: %d vlastností, 0 nullable\n", count((new ReflectionClass(Support\Customer::class))->getProperties()));

echo "\n    Ta nullable pole nejsou nedbalost. Jsou to místa, kde se model\n";
echo "    pokouší být třemi věcmi zároveň — a v žádném konkrétním okamžiku\n";
echo "    nedávají smysl všechna.\n";

// --- 3. Překlad na hranici -------------------------------------------------

echo "\n3. Překlad na hranici, ne kopie\n";

$payer = (new Billing\PayerFromWonDeal())->translate(
    $inSales,
    vatId: 'CZ27082440',
    billingAddress: 'Dlouhá 12, 110 00 Praha 1',
);

printf("    ze Sales se přeneslo:  identita, jméno firmy\n");
printf("    Sales o tom neví:      DIČ, adresa, splatnost\n");
printf("    Billing si dopočítal:  limit %s (pravidlo fakturace, ne obchodu)\n", money($payer->creditLimitInCents));
printf("    zahodilo se:           pravděpodobnost, account manager, kontaktní osoba\n");

echo "\n    Překlad vlastní PŘÍJEMCE (Billing), protože jen on ví, co potřebuje.\n";
echo "    Kdyby ho vlastnil Sales, musel by znát fakturační model — a hranice\n";
echo "    by tím přestala platit.\n";
