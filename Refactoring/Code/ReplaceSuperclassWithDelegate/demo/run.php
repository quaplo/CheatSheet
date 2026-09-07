<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka refaktoringu Replace Superclass with Delegate.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Before/PaidOrders.php';
require __DIR__ . '/After/PaidOrders.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function methods(int $n): string
{
    return match (true) {
        $n === 1 => '1 metoda',
        $n < 5 => $n . ' metody',
        default => $n . ' metod',
    };
}

$paid = [
    ['id' => 1, 'paid' => true, 'totalInCents' => 129000],
    ['id' => 2, 'paid' => true, 'totalInCents' => 45000],
];

$unpaid = ['id' => 3, 'paid' => false, 'totalInCents' => 999000];

echo "=== Replace Superclass with Delegate ===\n";
echo "    (dřív Replace Inheritance with Delegation)\n\n";

echo "    Kolekce, do které smějí jen zaplacené objednávky.\n";
echo "    Jednou zděděná z ArrayObject, podruhé s polem uvnitř.\n\n";

// --- 1. Zamýšlené chování --------------------------------------------------

echo "1. To, co jsme napsali, funguje v obou\n\n";

$before = new Before\PaidOrders();
$after = new After\PaidOrders();

foreach ($paid as $order) {
    $before->add($order);
    $after->add($order);
}

printf("    %s%s%s\n", pad('', 26), pad('dědičnost', 14), 'delegace');
printf("    %s%s%d\n", pad('count()', 26), pad((string) count($before), 14), count($after));
printf("    %s%s%d\n", pad('totalInCents()', 26), pad((string) $before->totalInCents(), 14), $after->totalInCents());

$rejected = static function (callable $add): string {
    try {
        $add();

        return 'PROŠLO — pravidlo neplatí';
    } catch (InvalidArgumentException) {
        return 'odmítnuto';
    }
};

printf(
    "    %s%s%s\n\n",
    pad('add() nezaplacené', 26),
    pad($rejected(static fn () => $before->add($unpaid)), 14),
    $rejected(static fn () => $after->add($unpaid)),
);

// --- 2. Co se zdědilo navíc ------------------------------------------------

echo "2. Co se zdědilo, aniž by o to někdo stál\n\n";

$beforeMethods = get_class_methods(Before\PaidOrders::class);
$afterMethods = get_class_methods(After\PaidOrders::class);
$own = ['add', 'totalInCents'];

printf("    %s%s%s\n", pad('', 34), pad('dědičnost', 16), 'delegace');
printf(
    "    %s%s%s\n",
    pad('veřejných metod celkem', 34),
    pad(methods(count($beforeMethods)), 16),
    methods(count($afterMethods)),
);
printf(
    "    %s%s%s\n\n",
    pad('z toho jsme jich napsali', 34),
    pad(methods(count($own)), 16),
    methods(count($own)),
);

$inherited = array_values(array_diff($beforeMethods, $own, ['__construct']));

printf("    Zděděné metody, kterými jde do kolekce sáhnout:\n\n");

foreach (array_slice(array_filter($inherited, static fn (string $m): bool => !str_starts_with($m, '__')), 0, 8) as $method) {
    printf("      · %s()\n", $method);
}

printf("      … a další, celkem %s\n\n", methods(count($inherited)));

// --- 3. Pravidlo obejité zděděnou metodou ---------------------------------

echo "3. A takhle se to pravidlo obejde\n\n";

$totalBefore = $before->totalInCents();

// Nikdo nemusí být zlomyslný — append() je na kolekci prostě vidět.
$before->append($unpaid);

printf("    %s%s\n", pad('dědičnost: append($unpaid)', 34), 'prošlo bez chyby');
printf(
    "    %s%d → %d\n",
    pad('totalInCents() se změnil z', 34),
    $totalBefore,
    $before->totalInCents(),
);
printf(
    "    %s%s\n\n",
    pad('delegace: append() vůbec není', 34),
    method_exists($after, 'append') ? 'JE — ukázka je rozbitá' : 'ano, ta metoda neexistuje',
);

echo "    Nikdo nemusel obcházet nic schválně. `append()` je na té\n";
echo "    kolekci vidět, v našeptávači i v dokumentaci ArrayObject —\n";
echo "    vypadá jako legitimní způsob, jak něco přidat.\n\n";

// --- 4. Substituce ---------------------------------------------------------

echo "4. A to horší: kam se ta kolekce dá předat\n\n";

printf("    %s%s\n", pad('dědičnost instanceof ArrayObject', 36), $before instanceof ArrayObject ? 'ano' : 'ne');
printf("    %s%s\n\n", pad('delegace instanceof ArrayObject', 36), $after instanceof ArrayObject ? 'ano' : 'ne');

echo "    Verze s dědičností projde všude, kde se čeká ArrayObject —\n";
echo "    včetně kódu, který do ní bez ptaní zapíše. Tomu se říká\n";
echo "    porušení LSP a je to důvod, proč ten refaktoring existuje.\n\n";

// --- 5. Co se za to platí --------------------------------------------------

echo "5. Co to stálo\n\n";

$beforeLines = count(file(__DIR__ . '/Before/PaidOrders.php'));
$afterLines = count(file(__DIR__ . '/After/PaidOrders.php'));

printf("    %s%s%d\n", pad('řádků', 26), pad((string) $beforeLines, 22), $afterLines);
printf(
    "    %s%s%s\n\n",
    pad('count() a iterace', 26),
    pad('zadarmo z předka', 22),
    'Countable + IteratorAggregate',
);

printf(
    "    Delegace stojí %d řádků navíc. Za ně má kolekce %s\n    místo %d — a všechny jsme napsali my.\n",
    $afterLines - $beforeLines,
    methods(count($afterMethods)),
    count($beforeMethods),
);
