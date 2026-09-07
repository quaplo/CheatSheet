<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka TDD refaktoringu — třetího kroku cyklu.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Suite.php';
require __DIR__ . '/WithoutStep3/PasswordPolicy.php';
require __DIR__ . '/WithStep3/PasswordPolicy.php';
require __DIR__ . '/BrokenStep3/PasswordPolicy.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function tests(int $n): string
{
    return match (true) {
        $n === 1 => '1 test',
        $n < 5 => $n . ' testy',
        default => $n . ' testů',
    };
}

/** Kolikrát soubor zná pravidlo o délce (literál 8) a kolik zpráv je v něm víc než jednou. */
function shape(string $file): array
{
    $tokens = token_get_all(file_get_contents($file));

    $lengthRule = 0;
    $messages = [];
    $complexity = 1;

    foreach ($tokens as $token) {
        if (!is_array($token)) {
            continue;
        }

        if ($token[0] === T_LNUMBER && $token[1] === '8') {
            ++$lengthRule;
        }

        if ($token[0] === T_CONSTANT_ENCAPSED_STRING && str_contains($token[1], 'heslo ')) {
            $messages[$token[1]] = ($messages[$token[1]] ?? 0) + 1;
        }

        if (in_array($token[0], [T_IF, T_ELSEIF, T_FOREACH, T_FOR, T_WHILE], true)) {
            ++$complexity;
        }

        if ($token[0] === T_BOOLEAN_AND || $token[0] === T_BOOLEAN_OR) {
            ++$complexity;
        }
    }

    return [
        'lines' => count(file($file)),
        'lengthRule' => $lengthRule,
        'duplicateMessages' => count(array_filter($messages, static fn (int $n): bool => $n > 1)),
        'complexity' => $complexity,
    ];
}

echo "=== TDD refactoring ===\n\n";

echo "    Fowlerův cyklus má tři kroky, ne dva:\n\n";
echo "      ① Add a Test      — přidej test, sada zčervená\n";
echo "      ② Make it work    — rozsviť ho, „simply but crudely\"\n";
echo "      ③ Make it Clean   — teprve teď návrh, v zelené\n\n";

// --- 1. Sada, která vyrostla ----------------------------------------------

echo "1. Čtyři cykly, čtyři pravidla\n\n";

$byCycle = [];

foreach (Suite::tests() as $test) {
    $byCycle[$test['cycle']][] = $test['name'];
}

$running = 0;

foreach ($byCycle as $cycle => $names) {
    $running += count($names);
    printf("    %s%s\n", pad('cyklus ' . $cycle, 14), 'sada má ' . tests($running));
}

echo "\n    Sada je pro všechny varianty níž stejná. Liší se jen to,\n";
echo "    jestli se po zelené udělal třetí krok.\n\n";

// --- 2. Bez třetího kroku a s ním -----------------------------------------

echo "2. Co z toho vyleze\n\n";

$variants = [
    'bez třetího kroku' => [new WithoutStep3\PasswordPolicy(), __DIR__ . '/WithoutStep3/PasswordPolicy.php'],
    's třetím krokem'   => [new WithStep3\PasswordPolicy(), __DIR__ . '/WithStep3/PasswordPolicy.php'],
];

printf(
    "    %s%s%s%s%s\n",
    pad('varianta', 22),
    pad('testy', 10),
    pad('řádků', 9),
    pad('míst s pravidlem', 20),
    'složitost',
);

foreach ($variants as $label => [$policy, $file]) {
    $result = Suite::runAgainst($policy);
    $shape = shape($file);

    printf(
        "    %s%s%s%s%d\n",
        pad($label, 22),
        pad($result['passed'] . '/' . count(Suite::tests()), 10),
        pad((string) $shape['lines'], 9),
        pad((string) $shape['lengthRule'], 20),
        $shape['complexity'],
    );
}

echo "\n    Obě verze projdou úplně stejnou sadu. Rozdíl není\n";
echo "    v chování — je v tom, kolikrát je každé pravidlo napsané.\n\n";

$without = shape(__DIR__ . '/WithoutStep3/PasswordPolicy.php');

printf("    %s%dx\n", pad('pravidlo o délce je v souboru', 34), $without['lengthRule']);
printf("    %s%d\n\n", pad('zpráv napsaných víc než jednou', 34), $without['duplicateMessages']);

echo "    Páté pravidlo se bude psát taky třikrát.\n\n";

// --- 3. Proč se refaktoruje až v zelené ------------------------------------

echo "3. Proč se třetí krok dělá až v zelené\n\n";

$broken = Suite::runAgainst(new BrokenStep3\PasswordPolicy());

echo "    Třetí krok udělaný o kousek jinak — array_filter() místo\n";
echo "    foreach. Vypadá to úsporněji a chová se to jinak:\n\n";

printf("    %s%s\n", pad('prošlo', 12), $broken['passed'] . '/' . count(Suite::tests()));
printf("    %s%s\n\n", pad('spadlo', 12), (string) count($broken['failed']));

$cycleOf = [];

foreach (Suite::tests() as $test) {
    $cycleOf[$test['name']] = $test['cycle'];
}

foreach ($broken['failed'] as $name) {
    printf("      · %s  (z cyklu %d)\n", $name, $cycleOf[$name]);
}

echo "\n    Chybu nenašel člověk ani nově psaný test. Našly ji testy,\n";
echo "    které v sadě v tu chvíli už byly — a to je celý důvod,\n";
echo "    proč se refaktoruje až po zelené.\n\n";

echo "    „You only refactor with green tests, and any test failing\n";
echo "     indicates a mistake.\"          — Martin Fowler, 2014\n";
