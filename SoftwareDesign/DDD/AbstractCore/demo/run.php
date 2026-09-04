<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Abstract Core.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Before/Retail/RetailOrder.php';
require __DIR__ . '/Before/Wholesale/WholesaleOrder.php';
require __DIR__ . '/Before/Subscription/SubscriptionOrder.php';

require __DIR__ . '/After/Core/Order.php';
require __DIR__ . '/After/Core/ComparesByTotal.php';
require __DIR__ . '/After/Retail/RetailOrder.php';
require __DIR__ . '/After/Wholesale/WholesaleOrder.php';
require __DIR__ . '/After/Subscription/SubscriptionOrder.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/**
 * Spočítá odkazy mezi moduly — kolik modulů zná kolik jiných.
 *
 * @param list<string> $files
 * @return array{edges: int, detail: list<string>}
 */
function crossModuleReferences(array $files, string $prefix): array
{
    $edges = 0;
    $detail = [];

    foreach ($files as $file) {
        $source = file_get_contents($file);
        $module = basename(dirname($file));

        preg_match_all('/^use ' . preg_quote($prefix, '/') . '\\\\(\w+)\\\\/m', $source, $matches);

        foreach (array_unique($matches[1]) as $target) {
            if ($target === $module) {
                continue;
            }

            ++$edges;
            $detail[] = sprintf('%s → %s', $module, $target);
        }
    }

    return ['edges' => $edges, 'detail' => $detail];
}

/** Počet veřejných metod deklarovaných přímo ve třídě. */
function publicMethods(string $class): int
{
    return count(array_filter(
        (new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC),
        static fn (ReflectionMethod $m): bool
            => $m->getDeclaringClass()->getName() === $class && !$m->isConstructor(),
    ));
}

echo "=== Abstract Core ===\n\n";

// --- 1. Kolik vazeb mezi moduly -------------------------------------------

echo "1. Kolik se toho moduly musí znát\n\n";

$beforeFiles = glob(__DIR__ . '/Before/*/*.php');
$afterFiles = array_merge(
    glob(__DIR__ . '/After/Retail/*.php'),
    glob(__DIR__ . '/After/Wholesale/*.php'),
    glob(__DIR__ . '/After/Subscription/*.php'),
);

$before = crossModuleReferences($beforeFiles, 'Before');
$after = crossModuleReferences($afterFiles, 'After');

printf("    %s%s\n", pad('', 20), 'vazeb mezi moduly');
printf("    %s%d\n", pad('Before', 20), $before['edges']);

foreach ($before['detail'] as $edge) {
    echo '        ' . $edge . "\n";
}

printf("    %s%d\n", pad('After', 20), $after['edges']);

foreach ($after['detail'] as $edge) {
    echo '        ' . $edge . "\n";
}

printf("\n    U tří modulů je vazeb %d. U pěti by jich bylo 20, u deseti 90 —\n", $before['edges']);
echo "    počet roste s druhou mocninou. Přes jádro roste lineárně.\n\n";

// --- 2. Jak velké je abstraktní jádro -------------------------------------

echo "2. Co všechno je v jádru\n\n";

$coreMethods = (new ReflectionClass(After\Core\Order::class))->getMethods();

printf("    rozhraní After\\Core\\Order — %d metody:\n", count($coreMethods));

foreach ($coreMethods as $method) {
    echo '        ' . $method->getName() . "()\n";
}

printf("\n    %s%s%s\n", pad('', 26), pad('v jádru', 12), 've svém modulu');
foreach ([
    'RetailOrder' => After\Retail\RetailOrder::class,
    'WholesaleOrder' => After\Wholesale\WholesaleOrder::class,
    'SubscriptionOrder' => After\Subscription\SubscriptionOrder::class,
] as $label => $class) {
    $total = publicMethods($class);
    printf("    %s%s%d\n", pad($label, 26), pad((string) count($coreMethods), 12), $total - count($coreMethods));
}

echo "\n    Jádro popisuje interakci, ne všechno. Balení jako dárek,\n";
echo "    splatnost a délka předplatného zůstaly ve svých modulech.\n\n";

// --- 3. Interakce popsaná jednou ------------------------------------------

echo "3. Porovnání napříč typy\n\n";

$retail = new After\Retail\RetailOrder('R-001', 129000, giftWrapped: true);
$wholesale = new After\Wholesale\WholesaleOrder('W-001', 890000, paymentTermInDays: 60);
$subscription = new After\Subscription\SubscriptionOrder('S-001', 348000, periodInMonths: 12);

/** @var list<After\Core\Order> $orders */
$orders = [$retail, $wholesale, $subscription];

usort($orders, static fn (After\Core\Order $a, After\Core\Order $b): int
    => $b->totalInCents() <=> $a->totalInCents());

echo "    seřazeno napříč typy (jedno usort přes rozhraní):\n";

foreach ($orders as $order) {
    printf(
        "        %s%s\n",
        pad($order->number(), 12),
        number_format($order->totalInCents() / 100, 2, ',', ' ') . ' Kč',
    );
}

printf(
    "\n    %s\n",
    'velkoobchodní > maloobchodní: ' . ($wholesale->isLargerThan($retail) ? 'ano' : 'ne'),
);

echo "\n    Metod isLargerThan* bylo v Before šest — každý typ potřeboval\n";
echo "    jednu pro každý jiný. Teď je jedna, v jádru.\n\n";

// --- 4. Co stojí přidání čtvrtého typu ------------------------------------

echo "4. Kolik stojí přidat čtvrtý typ objednávky\n\n";

printf("    %s%s%s\n", pad('', 30), pad('Before', 28), 'After');
printf("    %s%s%s\n", pad('nových tříd', 30), pad('1', 28), '1');
printf("    %s%s%s\n", pad('nových metod porovnání', 30), pad('3 (pro každý typ)', 28), '0');
printf("    %s%s%s\n", pad('změn v existujících modulech', 30), pad('3 (každý o něm musí vědět)', 28), '0');
printf("    %s%s%s\n\n", pad('nových vazeb mezi moduly', 30), pad('6', 28), '1 (na jádro)');

echo "    Tohle je ten rozdíl, kvůli kterému vzor existuje. V Before\n";
echo "    se přidáním typu musí sáhnout do všech ostatních modulů.\n";
