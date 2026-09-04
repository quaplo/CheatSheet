<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Ubiquitous Language.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/glossary.php';
require __DIR__ . '/Before/OrderService.php';
require __DIR__ . '/After/Cancellation.php';
require __DIR__ . '/After/Dispatch.php';
require __DIR__ . '/After/Complaint.php';
require __DIR__ . '/After/CreditNote.php';
require __DIR__ . '/After/Reservation.php';
require __DIR__ . '/After/Order.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/**
 * Najde v adresáři všechny identifikátory — jména tříd a metod.
 *
 * @return list<string>
 */
function identifiersIn(string $dir): array
{
    $names = [];

    foreach (glob($dir . '/*.php') as $file) {
        $tokens = token_get_all(file_get_contents($file));

        foreach ($tokens as $i => $token) {
            if (!is_array($token) || !in_array($token[0], [T_CLASS, T_FUNCTION], true)) {
                continue;
            }

            for ($j = $i + 1; $j < $i + 4; ++$j) {
                if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $names[] = $tokens[$j][1];
                    break;
                }
            }
        }
    }

    return $names;
}

/**
 * Kolik doménových pojmů je v kódu k nalezení.
 *
 * @param list<string> $identifiers
 * @return array{found: list<string>, missing: list<string>}
 */
function coverage(array $identifiers, array $terms): array
{
    $haystack = strtolower(implode(' ', $identifiers));
    $found = [];
    $missing = [];

    foreach ($terms as $czech => $english) {
        if (str_contains($haystack, strtolower($english))) {
            $found[] = $czech;
        } else {
            $missing[] = $czech;
        }
    }

    return ['found' => $found, 'missing' => $missing];
}

echo "=== Ubiquitous Language ===\n\n";

// --- 1. Slovník domény -----------------------------------------------------

echo "1. Jazyk, kterým mluví doména\n\n";

foreach (domainGlossary() as $term => $meaning) {
    printf("    %s%s\n", pad($term, 14), $meaning);
}

// --- 2. Kolik z toho je v kódu --------------------------------------------

echo "\n2. Kolik z toho jazyka je v kódu\n\n";

$terms = codeTerms();
$before = coverage(identifiersIn(__DIR__ . '/Before'), $terms);
$after = coverage(identifiersIn(__DIR__ . '/After'), $terms);

printf("    %s%s%s\n", pad('', 14), pad('nalezeno', 14), 'chybí');
printf("    %s%s%s\n", pad('Before', 14), pad(count($before['found']) . ' z ' . count($terms), 14), implode(', ', $before['missing']));
printf("    %s%s%s\n\n", pad('After', 14), pad(count($after['found']) . ' z ' . count($terms), 14), $after['missing'] === [] ? '—' : implode(', ', $after['missing']));

printf(
    "    pokrytí:      Before %.0f %%  ·  After %.0f %%\n\n",
    count($before['found']) / count($terms) * 100,
    count($after['found']) / count($terms) * 100,
);

// --- 3. Kolika slovy kód říká jednu věc -----------------------------------

echo "3. Jedna doménová věc, tři jména v kódu\n\n";

$cancellationSynonyms = ['abortOrder', 'revokeOrder', 'setStatusToInactive'];

echo "    Doména říká:      „storno\"\n";
printf("    Before říká:      %s\n", implode(', ', $cancellationSynonyms));
echo "    After říká:       cancel(Cancellation)\n\n";

echo "    Evans: „Translation blunts communication and makes\n";
echo "    knowledge crunching anemic.\" Když kód pro jednu věc\n";
echo "    používá tři slova, nikdo neví, jestli jsou to tři věci.\n\n";

// --- 4. Pojmy v kódu, které doména nezná ----------------------------------

echo "4. Pojmy v kódu, které doména nezná\n\n";

$foreign = [
    'processOrder'          => 'co je „process“? doména zná expedici, fakturaci, storno…',
    'createIssue'           => '„issue“ není doménový pojem — je to reklamace',
    'createNegativeInvoice' => 'doména tomu říká dobropis, ne „záporná faktura“',
    'lockStock'             => '„lock“ je technický pojem; doména zná rezervaci',
];

foreach ($foreign as $method => $why) {
    printf("    %s%s\n", pad($method . '()', 26), $why);
}

echo "\n    Tyhle pojmy vznikly u klávesnice, ne v rozhovoru.\n";
echo "    Doménový expert je při čtení kódu nepozná — a tím\n";
echo "    zmizí možnost, že kód po něm někdo zkontroluje.\n\n";

// --- 5. Změna jazyka je změna modelu --------------------------------------

echo "5. Co se stane, když se jazyk změní\n\n";

echo "    Doména zjistí, že „storno\" má dvě různé podoby:\n";
echo "        · storno zákazníkem před expedicí\n";
echo "        · zrušení obchodníkem kvůli nedostupnosti\n\n";

printf("    %s%s\n", pad('', 30), 'důsledek');
printf("    %s%s\n", pad('slovník', 30), 'dva pojmy místo jednoho');
printf("    %s%s\n", pad('model', 30), 'dvě události, ne jedna');
printf("    %s%s\n", pad('kód', 30), 'Cancellation → Cancellation + Withdrawal');
printf("    %s%s\n\n", pad('databáze, API, dokumentace', 30), 'přejmenovat všude');

echo "    Evans: „Recognize that a change in the language is\n";
echo "    a change to the model.\" Není to přejmenování —\n";
echo "    je to zjištění, že model byl chudší než skutečnost.\n";
