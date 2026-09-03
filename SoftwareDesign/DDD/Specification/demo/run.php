<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Specification.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Order.php';
require __DIR__ . '/OrderSpecification.php';
require __DIR__ . '/AndSpecification.php';
require __DIR__ . '/OrSpecification.php';
require __DIR__ . '/NotSpecification.php';
require __DIR__ . '/OrderIsPaid.php';
require __DIR__ . '/OrderTotalAtLeast.php';
require __DIR__ . '/OrderHasAtLeastItems.php';
require __DIR__ . '/OrderShipsTo.php';
require __DIR__ . '/EligibleForFreeShipping.php';

function formatPrice(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

$orders = [
    new Order('OBJ-001', 210000, 4, true, 'CZ'),
    new Order('OBJ-002', 89000, 2, true, 'CZ'),
    new Order('OBJ-003', 210000, 5, false, 'CZ'),
    new Order('OBJ-004', 340000, 1, true, 'SK'),
];

echo "=== Specification ===\n\n";

// --- 1. Pojmenované pravidlo -----------------------------------------------

echo "1. Pravidlo má jméno\n";

$freeShipping = new EligibleForFreeShipping();

foreach ($orders as $order) {
    printf(
        "    %s  %s  %d ks  %s  %s   →  %s\n",
        $order->number,
        mb_str_pad(formatPrice($order->totalInCents), 9, ' ', STR_PAD_LEFT),
        $order->itemCount,
        mb_str_pad($order->isPaid ? 'zaplaceno' : 'nezaplaceno', 11),
        $order->countryCode,
        $freeShipping->isSatisfiedBy($order) ? 'doprava zdarma' : '—',
    );
}

// --- 2. Vysvětlení, proč to neprošlo ---------------------------------------

echo "\n2. Proč to neprošlo\n";

foreach ($orders as $order) {
    $reasons = $freeShipping->reasonsForFailure($order);

    if ($reasons === []) {
        printf("    %s  vyhovuje\n", $order->number);

        continue;
    }

    printf("    %s  nevyhovuje, protože nesplňuje:\n", $order->number);

    foreach ($reasons as $reason) {
        printf("        · %s\n", $reason);
    }
}

// --- 3. Skládání za běhu ---------------------------------------------------

echo "\n3. Skládání pravidel za běhu\n";

$risky = (new OrderIsPaid())->not()
    ->and(new OrderTotalAtLeast(200000));

printf("    pravidlo: %s\n", $risky->describe());

foreach ($orders as $order) {
    if ($risky->isSatisfiedBy($order)) {
        printf("    ⚠ %s  %s\n", $order->number, formatPrice($order->totalInCents));
    }
}

// --- 4. Táž specifikace jako filtr -----------------------------------------

echo "\n4. Táž specifikace jako filtr kolekce\n";

$bulkOrLarge = (new OrderHasAtLeastItems(4))
    ->or(new OrderTotalAtLeast(300000));

printf("    pravidlo: %s\n", $bulkOrLarge->describe());

$matching = array_filter(
    $orders,
    static fn (Order $order): bool => $bulkOrLarge->isSatisfiedBy($order),
);

foreach ($matching as $order) {
    printf("    · %s  %s, %d ks\n", $order->number, formatPrice($order->totalInCents), $order->itemCount);
}

echo "\nTotéž pravidlo posloužilo k rozhodnutí, k vysvětlení i k filtrování.\n";
