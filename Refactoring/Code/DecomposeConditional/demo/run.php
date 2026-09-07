<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka refaktoringu Decompose Conditional.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Before/RefundPolicy.php';
require __DIR__ . '/After/RefundPolicy.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function lines(int $n): string
{
    return match (true) {
        $n === 1 => '1 řádek',
        $n < 5 => $n . ' řádky',
        default => $n . ' řádků',
    };
}

/** Délka metody v řádcích, změřená reflexí. */
function methodLines(string $class, string $method): int
{
    $reflection = new ReflectionMethod($class, $method);

    return $reflection->getEndLine() - $reflection->getStartLine() + 1;
}

/**
 * Rozhodovací body v metodě: if, &&, ||, ternární operátor, max().
 *
 * Záměrně ne součet cyklomatických složitostí — každá metoda do něj
 * přispívá základní jedničkou, takže by po rozdělení vyšel vyšší,
 * i kdyby se větvení vůbec nezměnilo.
 */
function decisionPoints(string $class, string $method): int
{
    $reflection = new ReflectionMethod($class, $method);
    $source = implode('', array_slice(
        file($reflection->getFileName()),
        $reflection->getStartLine() - 1,
        $reflection->getEndLine() - $reflection->getStartLine() + 1,
    ));

    $score = 0;

    foreach (token_get_all('<?php ' . $source) as $token) {
        if ($token === '?') {
            ++$score;
            continue;
        }

        if (!is_array($token)) {
            continue;
        }

        if (in_array($token[0], [T_IF, T_ELSEIF, T_FOREACH, T_FOR, T_WHILE, T_CASE], true)) {
            ++$score;
        }

        if (in_array($token[0], [T_BOOLEAN_AND, T_BOOLEAN_OR, T_COALESCE], true)) {
            ++$score;
        }

        // max() tu zastupuje podmínku „když je to záporné, vrať nulu".
        if ($token[0] === T_STRING && $token[1] === 'max') {
            ++$score;
        }
    }

    return $score;
}

/** @return list<string> jména metod třídy v pořadí, jak jsou v souboru */
function methodsOf(string $class): array
{
    return array_map(
        static fn (ReflectionMethod $m): string => $m->getName(),
        (new ReflectionClass($class))->getMethods(),
    );
}

/** Objednávky pokrývající všechny tři větve i jejich okraje. */
function orders(): array
{
    $day = 24 * 3600;
    $now = 1_700_000_000;
    $out = [];

    foreach ([null, $now - 3 * $day, $now - 20 * $day] as $shippedAt) {
        foreach (['karta', 'dobirka'] as $paymentMethod) {
            foreach ([true, false] as $giftWrapped) {
                foreach ([null, $now - $day] as $returnedAt) {
                    $out[] = [
                        [
                            'totalInCents' => 129000,
                            'shippingInCents' => 9900,
                            'paidAt' => $now - 10 * $day,
                            'shippedAt' => $shippedAt,
                            'returnedAt' => $returnedAt,
                            'paymentMethod' => $paymentMethod,
                            'giftWrapped' => $giftWrapped,
                        ],
                        $now,
                    ];
                }
            }
        }
    }

    // Okraj: vrácené zboží levnější než doprava — pojistka proti zápornému vrácení.
    $out[] = [[
        'totalInCents' => 5000,
        'shippingInCents' => 9900,
        'paidAt' => $now - 10 * $day,
        'shippedAt' => $now - 3 * $day,
        'returnedAt' => $now - $day,
        'paymentMethod' => 'karta',
        'giftWrapped' => true,
    ], $now];

    return $out;
}

echo "=== Decompose Conditional ===\n\n";

echo "    Metoda, která rozhoduje o vrácení peněz. Je správně,\n";
echo "    projde všechny testy — a nedá se přečíst.\n\n";

// --- 1. Chování ------------------------------------------------------------

echo "1. Nejdřív to podstatné: chování\n\n";

$before = new Before\RefundPolicy();
$after = new After\RefundPolicy();

$same = 0;
$cases = orders();

foreach ($cases as [$order, $now]) {
    if ($before->amountFor($order, $now) === $after->amountFor($order, $now)) {
        ++$same;
    }
}

printf("    %s%d\n", pad('případů', 22), count($cases));
printf("    %s%d\n\n", pad('shodných', 22), $same);

// --- 2. Kolik toho musíš přečíst ------------------------------------------

echo "2. Kolik toho musíš přečíst\n\n";

$beforeAll = methodLines(Before\RefundPolicy::class, 'amountFor');
$afterTop = methodLines(After\RefundPolicy::class, 'amountFor');

$branchChain = ['amountFor', 'isReturnAfterDelivery', 'refundWithoutShipping', 'giftWrapDeduction'];
$afterBranch = array_sum(array_map(
    static fn (string $m): int => methodLines(After\RefundPolicy::class, $m),
    $branchChain,
));

printf("    %s%s%s\n", pad('otázka', 46), pad('předtím', 14), 'potom');
printf(
    "    %s%s%s\n",
    pad('„co ta metoda vlastně dělá?“', 46),
    pad(lines($beforeAll), 14),
    lines($afterTop),
);
printf(
    "    %s%s%s\n\n",
    pad('„kolik dostane, kdo vrátil doručené zboží?“', 46),
    pad(lines($beforeAll), 14),
    lines($afterBranch),
);

echo "    Na druhou otázku vyjde skoro totéž — a to je poctivé.\n";
echo "    Decompose Conditional nezkracuje čtení. Umožňuje si vybrat,\n";
echo "    co číst nemusíš.\n\n";

// --- 3. Složitost ----------------------------------------------------------

echo "3. Co se stalo se složitostí\n\n";

$beforeMethods = array_filter(methodsOf(Before\RefundPolicy::class));
$afterMethods = array_filter(methodsOf(After\RefundPolicy::class));

$beforeScores = array_map(static fn (string $m): int => decisionPoints(Before\RefundPolicy::class, $m), $beforeMethods);
$afterScores = array_map(static fn (string $m): int => decisionPoints(After\RefundPolicy::class, $m), $afterMethods);

printf("    %s%s%s\n", pad('', 34), pad('předtím', 14), 'potom');
printf("    %s%s%d\n", pad('metod', 34), pad((string) count($beforeMethods), 14), count($afterMethods));
printf("    %s%s%d\n", pad('rozhodovacích bodů celkem', 34), pad((string) array_sum($beforeScores), 14), array_sum($afterScores));
printf("    %s%s%d\n\n", pad('nejvíc v jedné metodě', 34), pad((string) max($beforeScores), 14), max($afterScores));

printf(
    "    Větvení nikam nezmizelo, jen se rozpadlo do metod, které se\n    dají přečíst po jedné: z %d rozhodnutí v jedné metodě jsou nejvýš %d.\n\n",
    max($beforeScores),
    max($afterScores),
);

printf(
    "    Ten jeden bod, o který je jich celkem míň (%d → %d), není\n    zásluha rozkladu — je to duplicita, kterou rozklad zviditelnil.\n    Víc o ní v další části.\n\n",
    array_sum($beforeScores),
    array_sum($afterScores),
);

// --- 4. Co se přitom ukázalo ----------------------------------------------

echo "4. Co se přitom ukázalo\n\n";

$beforeSource = file_get_contents(__DIR__ . '/Before/RefundPolicy.php');
$duplicated = substr_count($beforeSource, "\$amount -= 2500;");

printf("    %s%dx\n", pad('sleva za dárkové balení byla v Before', 42), $duplicated);
printf("    %s%s\n\n", pad('v After', 42), 'jednou, jako giftWrapDeduction()');

echo "    Ta duplicita tam byla celou dobu. Nebyla vidět, protože\n";
echo "    obě kopie byly schované uvnitř dvou různých větví.\n\n";

// --- 5. Jména, která vznikla ----------------------------------------------

echo "5. Jména, která z toho vypadla\n\n";

foreach (array_diff($afterMethods, $beforeMethods) as $method) {
    printf("      · %s()\n", $method);
}

echo "\n    Ani jedno z nich jsme nevymysleli. Byla to pravidla,\n";
echo "    která v té podmínce ležela a neměla jméno.\n";
