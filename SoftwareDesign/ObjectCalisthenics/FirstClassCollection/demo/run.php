<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu First Class Collection.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/OrderItem.php';
require __DIR__ . '/OrderItems.php';
require __DIR__ . '/Order.php';

/** Formátování haléřů na koruny, jen kvůli čitelnému výpisu. */
function formatPrice(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}

echo "=== First Class Collection: položky objednávky ===\n\n";

// Sestavení objednávky. Každé withItem() vrací novou instanci.
$order = Order::empty('OBJ-001')
    ->withItem(new OrderItem('Klávesnice', 129000, 1, 900))
    ->withItem(new OrderItem('Myš', 45000, 2, 120))
    ->withItem(new OrderItem('Monitor 27"', 799000, 1, 6200))
    ->withItem(new OrderItem('Kabel HDMI', 29000, 3, 180));

echo "Objednávka {$order->number}\n";

foreach ($order->items as $item) {
    // mb_str_pad, protože printf počítá bajty a diakritika by zarovnání rozhodila
    printf(
        "    %s %d× %s   %5d g\n",
        mb_str_pad($item->productName, 14),
        $item->quantity,
        mb_str_pad(formatPrice($item->total()), 11, ' ', STR_PAD_LEFT),
        $item->weight(),
    );
}

printf("\n  Počet položek:   %d\n", count($order->items));
printf("  Celkem:          %s\n", formatPrice($order->total()));
printf("  Hmotnost:        %d g\n", $order->items->totalWeight());

// Filtrování vrací zase OrderItems — jde na něm rovnou volat total().
$heavy = $order->items->heavierThan(500);

printf(
    "\n  Těžké položky (>500 g): %d, dohromady %s a %d g\n",
    count($heavy),
    formatPrice($heavy->total()),
    $heavy->totalWeight(),
);

// Odebrání položky, opět bez mutace původní kolekce.
$withoutMonitor = $order->items->withoutProduct('Monitor 27"');

printf(
    "  Bez monitoru:           %d položek, %s\n",
    count($withoutMonitor),
    formatPrice($withoutMonitor->total()),
);
printf("  Původní kolekce beze změny: %d položek, %s\n", count($order->items), formatPrice($order->total()));

// Invariant se hlídá na jednom místě — kolekce se nedostane do špatného stavu.
echo "\n";

try {
    $tooMany = array_fill(0, 21, new OrderItem('Cokoli', 1000, 1, 10));
    OrderItems::fromArray($tooMany);
} catch (InvalidArgumentException $e) {
    echo 'Chyba: ' . $e->getMessage() . "\n";
}

// Prázdná kolekce je pořád plnohodnotná kolekce, ne null.
$empty = OrderItems::empty();
printf(
    "Prázdná objednávka: %d položek, %s, isEmpty() = %s\n",
    count($empty),
    formatPrice($empty->total()),
    $empty->isEmpty() ? 'true' : 'false',
);
