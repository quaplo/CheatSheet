<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Cohesive Mechanism.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/BoxSize.php';
require __DIR__ . '/PackableItem.php';
require __DIR__ . '/Packing/PackingPlan.php';
require __DIR__ . '/Packing/PackedBox.php';
require __DIR__ . '/Packing/Packer.php';
require __DIR__ . '/Packing/FirstFitDecreasingPacker.php';
require __DIR__ . '/Before/Shipment.php';
require __DIR__ . '/After/Shipment.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/**
 * Rozdělí metody třídy na doménové a algoritmické.
 *
 * @return array{domain: list<string>, mechanism: list<string>}
 */
function classifyMethods(string $class, array $domainNames): array
{
    $domain = [];
    $mechanism = [];

    foreach ((new ReflectionClass($class))->getMethods() as $method) {
        if ($method->getDeclaringClass()->getName() !== $class || $method->isConstructor()) {
            continue;
        }

        if (in_array($method->getName(), $domainNames, true)) {
            $domain[] = $method->getName();
        } else {
            $mechanism[] = $method->getName();
        }
    }

    return ['domain' => $domain, 'mechanism' => $mechanism];
}

/** České skloňování počtu krabic. */
function boxes(int $count): string
{
    $word = match (true) {
        $count === 1 => 'krabice',
        $count < 5 => 'krabice',
        default => 'krabic',
    };

    return $count . ' ' . $word;
}

function codeLines(string $file): int
{
    $lines = 0;

    foreach (token_get_all(file_get_contents($file)) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        $lines += substr_count(is_array($token) ? $token[1] : $token, "\n");
    }

    return $lines;
}

echo "=== Cohesive Mechanism ===\n\n";

$items = [
    new PackableItem('MON-27', 18000),
    new PackableItem('KLA-01', 4200),
    new PackableItem('MYS-01', 900),
    new PackableItem('KAB-HDMI', 300),
    new PackableItem('SLU-01', 3100),
    new PackableItem('POD-01', 1500),
];

$boxes = [
    new BoxSize('S', 5000, 1200),
    new BoxSize('M', 12000, 1900),
    new BoxSize('L', 25000, 2900),
];

// --- 1. Co algoritmus udělá s doménovou třídou -----------------------------

echo "1. „Co\" se ztrácí pod „jak\"\n\n";

$domainMethods = ['addItem', 'offerBox', 'canBeDispatched', 'dispatch'];

$before = classifyMethods(Before\Shipment::class, $domainMethods);
$after = classifyMethods(After\Shipment::class, [...$domainMethods, 'packUsing']);

printf("    %s%s%s\n", pad('', 26), pad('doménových metod', 20), 'algoritmických');
printf("    %s%s%d\n", pad('Before\\Shipment', 26), pad((string) count($before['domain']), 20), count($before['mechanism']));
printf("    %s%s%d\n\n", pad('After\\Shipment', 26), pad((string) count($after['domain']), 20), count($after['mechanism']));

echo "    algoritmické metody v Before:\n";

foreach ($before['mechanism'] as $name) {
    echo '        ' . $name . "()\n";
}

printf(
    "\n    řádků kódu:            Before %d  ·  After %d\n\n",
    codeLines(__DIR__ . '/Before/Shipment.php'),
    codeLines(__DIR__ . '/After/Shipment.php'),
);

echo "    Doménových metod je stejně. Zmizelo to, co s doménou\n";
echo "    nesouvisí — a s ním dvě třetiny kódu třídy.\n\n";

// --- 2. Mechanismus funguje stejně ----------------------------------------

echo "2. Výsledek je totožný\n\n";

$beforeShipment = new Before\Shipment('2026/001');
$afterShipment = new After\Shipment('2026/001');

foreach ($items as $item) {
    $beforeShipment->addItem($item);
    $afterShipment->addItem($item);
}

foreach ($boxes as $box) {
    $beforeShipment->offerBox($box);
    $afterShipment->offerBox($box);
}

$oldResult = $beforeShipment->packIntoBoxes();
$plan = $afterShipment->packUsing(new Packing\FirstFitDecreasingPacker());

printf("    Before: %s\n", boxes(count($oldResult)));
printf("    After:  %s\n\n", boxes($plan->boxCount()));

foreach ($plan->boxes as $i => $box) {
    printf(
        "    krabice %d (%s):  %s   využití %.1f %%\n",
        $i + 1,
        $box->size->name,
        implode(', ', array_map(static fn (PackableItem $it): string => $it->sku, $box->items)),
        $box->utilisationPercent(),
    );
}

printf("\n    cena obalů:            %s Kč\n\n", number_format($plan->totalPriceInCents() / 100, 2, ',', ' '));

// --- 3. Co doména o mechanismu ví ------------------------------------------

echo "3. Doména nezná algoritmus, jen záměr\n\n";

$uses = [];

foreach (token_get_all(file_get_contents(__DIR__ . '/After/Shipment.php')) as $i => $token) {
    if (is_array($token) && $token[0] === T_STRING && str_contains($token[1], 'Packer')) {
        $uses[] = $token[1];
    }
}

printf("    After\\Shipment zmiňuje:   %s\n", implode(', ', array_unique($uses)));
printf("    je to rozhraní?           %s\n", (new ReflectionClass(Packing\Packer::class))->isInterface() ? 'ano' : 'ne');
printf("    zná FirstFitDecreasing?   %s\n\n", str_contains(file_get_contents(__DIR__ . '/After/Shipment.php'), 'FirstFitDecreasing') ? 'ano' : 'ne');

echo "    Vyměnit heuristiku za jinou — nebo za volání externí služby —\n";
echo "    znamená předat jinou implementaci. Doména se nezmění.\n\n";

// --- 4. Mechanismus je testovatelný sám o sobě -----------------------------

echo "4. Mechanismus jde ověřit bez domény\n\n";

$packer = new Packing\FirstFitDecreasingPacker();

$cases = [
    'prázdný vstup' => [[], $boxes],
    'jedna malá položka' => [[new PackableItem('X', 100)], $boxes],
    'položka přesně na kapacitu S' => [[new PackableItem('X', 5000)], $boxes],
];

foreach ($cases as $label => [$caseItems, $caseBoxes]) {
    $result = $packer->pack($caseItems, $caseBoxes);
    printf("    %s%s\n", pad($label, 38), boxes($result->boxCount()));
}

try {
    $packer->pack([new PackableItem('OBR', 99000)], $boxes);
} catch (InvalidArgumentException $e) {
    printf("    %s%s\n", pad('položka větší než největší krabice', 38), $e->getMessage());
}

echo "\n    Žádná zásilka, žádná objednávka, žádná databáze —\n";
echo "    jen objemy a kapacity. To je ta soudržnost z názvu.\n";
