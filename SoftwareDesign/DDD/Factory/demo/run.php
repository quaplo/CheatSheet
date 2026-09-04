<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Factory (DDD).
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Money.php';
require __DIR__ . '/OrderLine.php';
require __DIR__ . '/Order.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

echo "=== Factory (DDD) ===\n\n";

// --- 1. Celý agregát jedním voláním ---------------------------------------

echo "1. Celý agregát najednou\n\n";

$order = Order::place('2026/001', [
    new OrderLine('MON-27', 1, Money::fromCents(299000)),
    new OrderLine('KLA-01', 2, Money::fromCents(49000)),
], 'na dobírku');

printf("    objednávka:            %s\n", $order->number);
printf("    položek:               %d\n", count($order->lines()));
printf("    celkem:                %s   ← spočítala továrna, ne volající\n", $order->total()->format());
printf("    platba:                %s\n\n", $order->paymentMethod());

echo "    Volající předal položky a dostal platnou objednávku.\n";
echo "    Nemusel počítat součet ani znát pravidla.\n\n";

// --- 2. Co továrna nepustí -------------------------------------------------

echo "2. Invarianty, které továrna vynutí\n\n";

$attempts = [
    'objednávka bez položek' => static fn (): Order => Order::place('2026/002', [], 'na dobírku'),

    'stejné SKU dvakrát' => static fn (): Order => Order::place('2026/003', [
        new OrderLine('MON-27', 1, Money::fromCents(299000)),
        new OrderLine('MON-27', 1, Money::fromCents(299000)),
    ], 'na dobírku'),

    'platba předem nad limit' => static fn (): Order => Order::place('2026/004', [
        new OrderLine('MON-27', 3, Money::fromCents(299000)),
    ], 'předem'),
];

foreach ($attempts as $label => $attempt) {
    try {
        $attempt();
        printf("    %s%s\n", pad($label, 30), 'prošlo — CHYBA');
    } catch (DomainException $e) {
        printf("    %s%s\n", pad($label, 30), $e->getMessage());
    }
}

echo "\n    Evans: „Create an entire aggregate as a piece,\n";
echo "    enforcing its invariants.\"\n\n";

// --- 3. Proč konstruktor nestačí ------------------------------------------

echo "3. Proč je konstruktor soukromý\n\n";

$constructor = (new ReflectionClass(Order::class))->getConstructor();

printf("    konstruktor je:        %s\n", $constructor->isPrivate() ? 'private' : 'public');
printf("    parametrů:             %d\n", $constructor->getNumberOfParameters());

echo "\n    Kdyby byl veřejný, volající by musel:\n";
echo "        1. spočítat součet položek správně\n";
echo "        2. ověřit, že SKU se neopakují\n";
echo "        3. znát limit pro platbu předem\n";
echo "        4. udělat to všechno znovu na každém místě, kde vzniká objednávka\n\n";

echo "    Evans: „Making the client direct construction muddies the design\n";
echo "    of the client, breaches encapsulation of the assembled object.\"\n\n";

// --- 4. Vytvoření vs. rekonstrukce ----------------------------------------

echo "4. Vytvoření není rekonstrukce\n\n";

// Objednávka, která by dnes neprošla — limit se mezitím snížil.
$fromDatabase = Order::reconstitute(
    '2019/117',
    [new OrderLine('STARY-KUS', 1, Money::fromCents(1200000))],
    Money::fromCents(1200000),
    'předem',
);

printf("    %s%s%s\n", pad('', 26), pad('place()', 22), 'reconstitute()');
printf("    %s%s%s\n", pad('kontroluje invarianty', 26), pad('ano', 22), 'ne');
printf("    %s%s%s\n", pad('kdy se použije', 26), pad('nová objednávka', 22), 'načtení z databáze');
printf("    %s%s%s\n\n", pad('kdo ji volá', 26), pad('doména', 22), 'repository / mapper');

printf("    stará objednávka z roku 2019:  %s, platba %s\n", $fromDatabase->total()->format(), $fromDatabase->paymentMethod());

try {
    Order::place('2019/117', $fromDatabase->lines(), 'předem');
    echo "    přes place() by dnes:          prošla\n\n";
} catch (DomainException $e) {
    echo "    přes place() by dnes:          neprošla\n\n";
}

echo "    Kdyby rekonstrukce kontrolovala pravidla, nešlo by načíst\n";
echo "    objednávku, která byla platná podle starých pravidel.\n";
echo "    To je nejčastější důvod, proč se tyhle dvě cesty oddělují.\n\n";

// --- 5. Kolik cest vede k objednávce --------------------------------------

echo "5. Kolik veřejných cest vede k agregátu\n\n";

$methods = array_filter(
    (new ReflectionClass(Order::class))->getMethods(ReflectionMethod::IS_PUBLIC),
    static fn (ReflectionMethod $m): bool => $m->isStatic(),
);

foreach ($methods as $method) {
    printf("    %s%s\n", pad($method->getName() . '()', 22), $method->getName() === 'place' ? 'nová objednávka, kontroluje' : 'z databáze, nekontroluje');
}

printf("\n    veřejných konstruktorů:  0\n");
printf("    pojmenovaných cest:      %d\n\n", count($methods));

echo "    Obě cesty mají jméno, které říká, co dělají. `new Order(...)`\n";
echo "    by neřeklo nic — a hlavně by nešlo rozlišit, jestli objednávka\n";
echo "    právě vzniká, nebo se jen načítá.\n";
