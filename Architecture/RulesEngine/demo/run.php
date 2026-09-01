<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Rules Engine.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/DiscountContext.php';
require __DIR__ . '/DiscountRule.php';
require __DIR__ . '/ConflictResolution.php';
require __DIR__ . '/AppliedRule.php';
require __DIR__ . '/DiscountResult.php';
require __DIR__ . '/DiscountEngine.php';
require __DIR__ . '/VipCustomerRule.php';
require __DIR__ . '/FirstOrderRule.php';
require __DIR__ . '/PromoCodeRule.php';
require __DIR__ . '/ConfiguredRule.php';

function money(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

/** @return list<DiscountRule> */
function rules(): array
{
    return [
        new VipCustomerRule(),
        new FirstOrderRule(),
        new PromoCodeRule('PODZIM26', 15),

        // Poslední dvě jsou poskládaná z konfigurace, ne napsaná v PHP.
        ConfiguredRule::fromArray([
            'name' => 'Objednávka nad 5 000 Kč −5 %',
            'priority' => 70,
            'when' => ['field' => 'orderTotalInCents', 'op' => '>=', 'value' => 500000],
            'then' => ['percent' => 5],
        ]),
        ConfiguredRule::fromArray([
            'name' => 'Velkoodběr od 20 kusů −500 Kč',
            'priority' => 60,
            'when' => ['field' => 'itemCount', 'op' => '>=', 'value' => 20],
            'then' => ['amount' => 50000],
        ]),
    ];
}

echo "=== Rules Engine ===\n\n";

// --- 1. Pravidla se dají vypsat -------------------------------------------

echo "1. Katalog pravidel\n";
echo "   (na otázku „jaké máme slevy?“ existuje odpověď — z ifů ji nedostaneš)\n\n";

foreach ((new DiscountEngine(rules()))->catalogue() as $entry) {
    printf("        [%3d]  %s\n", $entry['priority'], $entry['name']);
}

// --- 2. Auditní stopa ------------------------------------------------------

$order = new DiscountContext(
    orderNumber: 'OBJ-001',
    orderTotalInCents: 620000,
    itemCount: 24,
    isVipCustomer: true,
    isFirstOrder: false,
    promoCode: 'PODZIM26',
);

echo "\n2. Vyhodnocení s auditní stopou\n";
printf("   objednávka %s, %s, %d kusů, VIP\n\n", $order->orderNumber, money($order->orderTotalInCents), $order->itemCount);

$result = (new DiscountEngine(rules()))->evaluate($order);

printf("        strategie: %s\n", $result->strategy->label());
printf("        sleva:     %s\n\n", money($result->totalDiscountInCents));

echo "        uplatněno:\n";

foreach ($result->used() as $rule) {
    printf("            ✓ %s %s\n", mb_str_pad($rule->name, 32), money($rule->discountInCents));
}

echo "        sedlo, ale neuplatnilo se:\n";

foreach ($result->shadowed() as $rule) {
    printf("            · %s %s\n", mb_str_pad($rule->name, 32), money($rule->discountInCents));
}

// --- 3. Strategie řešení konfliktů je byznysové rozhodnutí ----------------

echo "\n3. Táž objednávka, tři strategie\n";
echo "   Rozdíl mezi nimi není technický — je to otázka na produkťáka.\n\n";

foreach (ConflictResolution::cases() as $strategy) {
    $outcome = (new DiscountEngine(rules(), $strategy))->evaluate($order);

    printf(
        "        %s %10s   (uplatněno pravidel: %d)\n",
        mb_str_pad($strategy->label(), 32),
        money($outcome->totalDiscountInCents),
        count($outcome->used()),
    );
}

// --- 4. Chyba v konfiguraci se pozná až za běhu ---------------------------

echo "\n4. Cena za pravidla mimo kód\n";

$typo = new DiscountEngine([
    ConfiguredRule::fromArray([
        'name' => 'Překlep v konfiguraci',
        'priority' => 10,
        'when' => ['field' => 'orderTotalCents', 'op' => '>=', 'value' => 1000],  // chybí „In“
        'then' => ['percent' => 5],
    ]),
]);

try {
    $typo->evaluate($order);
} catch (InvalidArgumentException $e) {
    printf("        %s\n", $e->getMessage());
    echo "        ↑ v pravidle psaném v PHP by tohle nepustil ani PHPStan\n";
}

// --- 5. Diagnostika mrtvých pravidel --------------------------------------

echo "\n5. Které pravidlo nikdy nesedne?\n";

$samples = [
    $order,
    new DiscountContext('OBJ-002', 89000, 2, false, true, null),
    new DiscountContext('OBJ-003', 1200000, 30, false, false, null),
    new DiscountContext('OBJ-004', 250000, 5, false, false, 'JARO25'),
];

$dead = (new DiscountEngine(rules()))->neverMatching($samples);

if ($dead === []) {
    echo "        žádné — všechna pravidla na některém vzorku sedla\n";
} else {
    foreach ($dead as $name) {
        printf("        ⚠ %s\n", $name);
    }

    echo "        ↑ mrtvé pravidlo v hromadě ifů nikdo nenajde\n";
}
