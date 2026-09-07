<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka refaktoringu Replace Conditional with Polymorphism.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Shipment.php';
require __DIR__ . '/Before/ShippingService.php';
require __DIR__ . '/After/ShippingMethod.php';
require __DIR__ . '/After/Ppl.php';
require __DIR__ . '/After/Dhl.php';
require __DIR__ . '/After/Pickup.php';
require __DIR__ . '/After/Balikovna.php';
require __DIR__ . '/After/ShippingMethods.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/** Spočítá větvení podle typu — match a switch, bez komentářů. */
function branchingCount(string $file): int
{
    $count = 0;

    foreach (token_get_all(file_get_contents($file)) as $token) {
        if (is_array($token) && in_array($token[0], [T_MATCH, T_SWITCH], true)) {
            ++$count;
        }
    }

    return $count;
}

function czk(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}

echo "=== Replace Conditional with Polymorphism ===\n\n";

// --- 1. Kolikrát se větví podle téhož ---------------------------------------

echo "1. Tentýž `match` na čtyřech místech\n\n";

$beforeBranches = branchingCount(__DIR__ . '/Before/ShippingService.php');

$afterBranches = 0;

foreach (glob(__DIR__ . '/After/*.php') as $file) {
    $afterBranches += branchingCount($file);
}

printf("    %s%d\n", pad('Before — větvení na typ', 30), $beforeBranches);
printf("    %s%d\n\n", pad('After — větvení na typ', 30), $afterBranches);

echo "    Všechna zmizela. Rozhodnutí, který dopravce to je, se dělá\n";
echo "    jednou — v továrně na hranici — a pak už jen volá metoda.\n\n";

// --- 2. Chování zůstalo stejné ---------------------------------------------

echo "2. Chování se nezměnilo\n\n";

$before = new Before\ShippingService();
$methods = After\ShippingMethods::default();

$shipment = new Shipment('2026/001', 3200, 129000);

printf("    %s%s%s%s\n", pad('dopravce', 14), pad('Before', 16), pad('After', 16), 'shoda');

foreach (['ppl', 'dhl', 'pickup'] as $carrier) {
    $old = $before->priceInCents($carrier, $shipment);
    $new = $methods->byCode($carrier)->priceInCents($shipment);

    printf(
        "    %s%s%s%s\n",
        pad($carrier, 14),
        pad(czk($old), 16),
        pad(czk($new), 16),
        $old === $new ? 'ano' : 'NE',
    );
}

echo "\n";

// --- 3. Co stojí přidat dalšího dopravce -----------------------------------

echo "3. Přidání dalšího dopravce\n\n";

$methodsInBefore = count((new ReflectionClass(Before\ShippingService::class))->getMethods());

printf("    %s%s%s\n", pad('', 32), pad('Before', 24), 'After');
printf("    %s%s%s\n", pad('nových souborů', 32), pad('0', 24), '1');
printf("    %s%s%s\n", pad('změn v existujícím kódu', 32), pad($methodsInBefore . ' metod', 24), '0');
printf("    %s%s%s\n", pad('co hlídá úplnost', 32), pad('nic', 24), 'PHP');
printf("    %s%s%s\n\n", pad('kdy se pozná chyba', 32), pad('za běhu', 24), 'při vytváření třídy');

echo "    V Before se musí najít všechna čtyři místa. V After vznikne\n";
echo "    jeden soubor a do ničeho existujícího se nesahá.\n\n";

// --- 4. Na nic se nedá zapomenout ------------------------------------------

echo "4. PHP nedovolí zapomenout\n\n";

$required = array_map(
    static fn (ReflectionMethod $m): string => $m->getName(),
    (new ReflectionClass(After\ShippingMethod::class))->getMethods(ReflectionMethod::IS_ABSTRACT),
);

printf("    abstraktních metod v nadtřídě:  %d\n", count($required));
printf("    %s\n\n", '    ' . implode(', ', $required));

// Neúplnou třídu nelze zkusit uvnitř tohohle skriptu — chyba nastane
// při kompilaci a nedá se odchytit. Proto samostatný proces.
$output = shell_exec('php ' . escapeshellarg(__DIR__ . '/broken/incomplete.php') . ' 2>&1');
$firstLine = strtok(trim((string) $output), "\n");

printf("    neúplná třída:\n        %s\n\n", preg_replace('/ in .*/', '', $firstLine));

echo "    V Before by taková chyba prošla a spadla by až ve chvíli,\n";
echo "    kdy někdo objedná zrovna tímhle dopravcem.\n\n";

// --- 5. Nový dopravce v akci ------------------------------------------------

echo "5. Nový dopravce přidaný po refaktoringu\n\n";

$extended = new After\ShippingMethods(
    new After\Ppl(),
    new After\Dhl(),
    new After\Pickup(),
    new After\Balikovna(),
);

printf("    %s%s%s%s\n", pad('dopravce', 14), pad('cena', 14), pad('dnů', 8), 'adresa');

foreach ($extended->all() as $method) {
    printf(
        "    %s%s%s%s\n",
        pad($method->code(), 14),
        pad(czk($method->priceInCents($shipment)), 14),
        pad((string) $method->deliveryDays(), 8),
        $method->requiresAddress() ? 'ano' : 'ne',
    );
}

echo "\n    Výpis prochází všechny dopravce jedním cyklem. V Before by\n";
echo "    k tomu byl potřeba seznam řetězců — a ten by se taky musel\n";
echo "    ručně doplnit.\n";
