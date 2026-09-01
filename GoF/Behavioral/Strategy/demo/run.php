<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Strategy.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Order.php';
require __DIR__ . '/ShippingCost.php';
require __DIR__ . '/PickupPointShipping.php';
require __DIR__ . '/CourierShipping.php';
require __DIR__ . '/PersonalPickupShipping.php';
require __DIR__ . '/ShippingCalculator.php';

/** Formátování haléřů na koruny, jen kvůli čitelnému výpisu. */
function formatPrice(int $cents): string
{
    return $cents === 0 ? 'zdarma' : number_format($cents / 100, 2, ',', ' ') . ' Kč';
}

// Registrace strategií. V reálné aplikaci tohle udělá DI kontejner.
$calculator = new ShippingCalculator([
    new PersonalPickupShipping(),
    new PickupPointShipping(),
    new CourierShipping(),
]);

$orders = [
    new Order(number: 'OBJ-001', totalInCents: 89000, weightInGrams: 800, countryCode: 'CZ'),
    new Order(number: 'OBJ-002', totalInCents: 210000, weightInGrams: 1200, countryCode: 'CZ'),
    new Order(number: 'OBJ-003', totalInCents: 45000, weightInGrams: 8300, countryCode: 'CZ'),
    new Order(number: 'OBJ-004', totalInCents: 45000, weightInGrams: 900, countryCode: 'SK'),
];

echo "=== Strategy: výpočet dopravy ===\n\n";

foreach ($orders as $order) {
    printf(
        "%s — %s, %.1f kg, %s\n",
        $order->number,
        formatPrice($order->totalInCents),
        $order->weightInGrams / 1000,
        $order->countryCode,
    );

    foreach ($calculator->availableOptions($order) as $code => $price) {
        printf("    %-16s %s\n", $code, formatPrice($price));
    }

    echo "\n";
}

// Zákazník si vybral konkrétní dopravu.
$chosen = $calculator->calculate($orders[2], 'courier');
echo 'Zvolená doprava pro OBJ-003 (courier): ' . formatPrice($chosen) . "\n";

// Neznámý kód dopravy skončí výjimkou, ne tichým nulovým poplatkem.
try {
    $calculator->calculate($orders[0], 'drone');
} catch (InvalidArgumentException $e) {
    echo 'Chyba: ' . $e->getMessage() . "\n";
}
