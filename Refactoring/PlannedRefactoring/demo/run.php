<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka plánovaného refaktoringu.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Rule.php';

foreach (['Step0', 'Inc1', 'Inc2', 'Inc3', 'Abandoned'] as $state) {
    foreach (glob(__DIR__ . '/' . $state . '/*.php') as $file) {
        require $file;
    }
}

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function places(int $n): string
{
    return match (true) {
        $n === 1 => '1 místo',
        $n < 5 => $n . ' místa',
        default => $n . ' míst',
    };
}

/** Vstupy pokrývají obě pravidla i jejich strop. */
function cases(): array
{
    $out = [];

    foreach ([10000, 99900, 250000] as $total) {
        foreach ([1, 99, 100, 500] as $quantity) {
            foreach ([1, 2, 3] as $tier) {
                $out[] = [$total, $quantity, $tier];
            }
        }
    }

    return $out;
}

/** Sestaví trojici tříd daného stavu a přečte z nich chování. */
function behaviourOf(string $ns): array
{
    $policyClass = $ns . '\\DiscountPolicy';
    $policy = class_exists($policyClass) ? new $policyClass() : null;

    $build = static function (string $class) use ($policy): object {
        return (new ReflectionClass($class))->getConstructor() === null
            ? new $class()
            : new $class($policy);
    };

    $export = $build($ns . '\\OrderExport');
    $invoice = $build($ns . '\\InvoicePdf');
    $summary = $build($ns . '\\OrderSummary');

    $out = [];

    foreach (cases() as [$total, $quantity, $tier]) {
        $out[] = implode('|', [
            $export->row($total, $quantity, $tier),
            $invoice->totalLine($total, $quantity, $tier),
            $summary->forAdmin($total, $quantity, $tier),
        ]);
    }

    return $out;
}

function differences(array $left, array $right): int
{
    $n = 0;

    foreach ($left as $i => $value) {
        if ($value !== $right[$i]) {
            ++$n;
        }
    }

    return $n;
}

echo "=== Plánovaný refaktoring ===\n\n";

echo "    Refaktoringová story: „Sjednotit výpočet slevy do jednoho\n";
echo "    místa.\"  Pravidlo je dnes ve třech třídách — a mají ho\n";
echo "    každá po svém, včetně stropu 12 %.\n\n";

// --- 1. Výchozí stav -------------------------------------------------------

echo "1. Kde všude to pravidlo je\n\n";

foreach (Rule::placesIn(__DIR__ . '/Step0') as $file) {
    printf("    %s\n", $file);
}

echo "\n    Tohle na litter-pickup nestačí a přípravný refaktoring to\n";
echo "    není — žádná funkce se právě nepřidává. Je to práce, o které\n";
echo "    musí vědět tým. To je plánovaný refaktoring.\n\n";

// --- 2. Průběh po krocích --------------------------------------------------

echo "2. Story rozdělená na tři kroky\n\n";

$states = [
    'výchozí stav'                 => 'Step0',
    'krok 1: OrderExport'          => 'Inc1',
    'krok 2: InvoicePdf'           => 'Inc2',
    'krok 3: OrderSummary'         => 'Inc3',
];

$base = behaviourOf('Step0');
$total = count(cases());

printf(
    "    %s%s%s%s\n",
    pad('stav', 26),
    pad('míst s pravidlem', 20),
    pad('běží?', 9),
    'chování shodné?',
);

foreach ($states as $label => $ns) {
    $found = Rule::placesIn(__DIR__ . '/' . $ns);
    $diff = differences($base, behaviourOf($ns));

    printf(
        "    %s%s%s%s\n",
        pad($label, 26),
        pad((string) count($found), 20),
        pad('ano', 9),
        $diff === 0 ? 'ano (' . $total . '/' . $total . ')' : 'NE — ' . $diff . ' rozdílů',
    );
}

echo "\n    Všimni si prvního kroku: míst je pořád stejně. DiscountPolicy\n";
echo "    přibyla, ale dvě třídy si pravidlo drží dál. To je ta část,\n";
echo "    která se nejhůř obhajuje — zaplatila se a nic se nezlepšilo.\n\n";

// --- 3. Co když se to nedodělá --------------------------------------------

echo "3. Co zbude, když se to přeruší\n\n";

$abandoned = Rule::placesIn(__DIR__ . '/Abandoned');
$abandonedDiff = differences($base, behaviourOf('Abandoned'));

printf("    %s%s\n", pad('přerušeno po kroku 1 (po krocích)', 38), places(count(Rule::placesIn(__DIR__ . '/Inc1'))));
printf("    %s%s\n", pad('přerušeno po kroku 2 (po krocích)', 38), places(count(Rule::placesIn(__DIR__ . '/Inc2'))));
printf("    %s%s\n", pad('přerušeno uprostřed velkého třesku', 38), places(count($abandoned)));
printf("    %s%s\n\n", pad('výchozí stav pro srovnání', 38), places(count(Rule::placesIn(__DIR__ . '/Step0'))));

foreach ($abandoned as $file) {
    printf("    %s\n", $file);
}

printf(
    "\n    Přerušený velký třesk je HORŠÍ než výchozí stav — a přitom\n    se nic nerozbilo: chování je shodné (%d/%d).\n",
    $total - $abandonedDiff,
    $total,
);

echo "    Nikdo si toho nevšimne. Jen to pravidlo je teď na čtyřech\n";
echo "    místech místo tří.\n\n";

// --- 4. Kde se dá zastavit -------------------------------------------------

echo "4. Kde se dá zastavit\n\n";

$startPlaces = count(Rule::placesIn(__DIR__ . '/Step0'));

/** Bezpečné zastavení = běží, chová se stejně a míst není víc než na začátku. */
$isSafe = static function (string $ns) use ($base, $startPlaces): bool {
    return differences($base, behaviourOf($ns)) === 0
        && count(Rule::placesIn(__DIR__ . '/' . $ns)) <= $startPlaces;
};

$incremental = count(array_filter(['Inc1', 'Inc2', 'Inc3'], $isSafe));
$bigBang = count(array_filter(['Inc3'], $isSafe));
$abandonedSafe = $isSafe('Abandoned');

printf("    %s%s%s\n", pad('postup', 24), pad('bezpečných zastávek', 23), 'když se zastaví jinde');
printf(
    "    %s%s%s\n",
    pad('po krocích', 24),
    pad((string) $incremental, 23),
    'jiná místa nejsou',
);
printf(
    "    %s%s%s\n\n",
    pad('velký třesk', 24),
    pad($bigBang . ' — až na konci', 23),
    $abandonedSafe ? 'v pořádku' : places(count($abandoned)) . ' místo ' . $startPlaces,
);

echo "    Práce je v obou případech stejně velká. Liší se jen tím,\n";
echo "    kolikrát se dá odejít a nechat to v pořádku.\n\n";

echo "    „Since all changes are refactorings, the code base can remain\n";
echo "     in a working state even as features are added.\"\n";
echo "                                       — Martin Fowler, 2014\n";
