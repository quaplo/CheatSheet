<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka jističe.
 *
 * Čas je simulovaný, takže demo doběhne okamžitě a při každém
 * spuštění dá totéž.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/FlakyGateway.php';
require __DIR__ . '/CircuitBreaker.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function seconds(float $value): string
{
    return sprintf('%.1f s', $value);
}

const BROKEN_FROM = 10.0;
const BROKEN_UNTIL = 70.0;
const THRESHOLD = 3;
const OPEN_SECONDS = 20.0;

/** Platby přicházejí každou sekundu po dobu dvou minut. */
function requests(): array
{
    return array_map(static fn (int $i): float => (float) $i, range(0, 119));
}

echo "=== Circuit Breaker (jistič) ===\n\n";

printf(
    "    Platební brána je mimo provoz od %s do %s.\n",
    seconds(BROKEN_FROM),
    seconds(BROKEN_UNTIL),
);
printf("    Neodmítá — mlčí, takže každé volání doběhne až na timeout %s.\n\n", seconds(FlakyGateway::TIMEOUT_SECONDS));

// --- 1. Bez jističe --------------------------------------------------------

echo "1. Bez jističe\n\n";

$plain = new FlakyGateway(BROKEN_FROM, BROKEN_UNTIL);
$plainFailed = 0;

foreach (requests() as $now) {
    if (!$plain->charge($now)['ok']) {
        ++$plainFailed;
    }
}

printf("    %s%d\n", pad('požadavků', 32), count(requests()));
printf("    %s%d\n", pad('volání na bránu', 32), $plain->calls());
printf("    %s%d\n", pad('z toho neúspěšných', 32), $plainFailed);
printf("    %s%s\n\n", pad('času stráveno čekáním', 32), seconds($plain->timeSpent()));

echo "    Šedesát požadavků se zaseklo na timeoutu. To není jen ztráta\n";
echo "    času — po tu dobu drží každý z nich vlákno nebo worker, a to\n";
echo "    je ta cesta, kterou se pád cizí služby přenese na tvoji.\n\n";

// --- 2. S jističem ---------------------------------------------------------

echo "2. S jističem\n\n";

$guarded = new FlakyGateway(BROKEN_FROM, BROKEN_UNTIL);
$breaker = new CircuitBreaker(THRESHOLD, OPEN_SECONDS);
$skipped = 0;
$failed = 0;

foreach (requests() as $now) {
    $result = $breaker->call(static fn (float $at): array => $guarded->charge($at), $now);

    if ($result['skipped']) {
        ++$skipped;
    } elseif (!$result['ok']) {
        ++$failed;
    }
}

printf("    %s%s%s\n", pad('', 32), pad('bez jističe', 16), 's jističem');
printf("    %s%s%d\n", pad('volání na bránu', 32), pad((string) $plain->calls(), 16), $guarded->calls());
printf(
    "    %s%s%s\n",
    pad('času stráveno čekáním', 32),
    pad(seconds($plain->timeSpent()), 16),
    seconds($guarded->timeSpent()),
);
printf("    %s%s%d\n\n", pad('odmítnuto bez volání', 32), pad('0', 16), $skipped);

printf(
    "    Čekání kleslo %.0f×. Jistič nezachránil ani jeden požadavek —\n    zachránil ČAS, který by na nich tvoje aplikace prostála.\n\n",
    $plain->timeSpent() / max($guarded->timeSpent(), 0.01),
);

// --- 3. Jak se to přepínalo ------------------------------------------------

echo "3. Jak se stavy přepínaly\n\n";

printf("    %s%s\n", pad('čas', 12), 'přechod');

foreach ($breaker->transitions() as $transition) {
    printf(
        "    %s%s → %s\n",
        pad(seconds($transition['at']), 12),
        $transition['from'],
        $transition['to'],
    );
}

echo "\n    Ten sled „otevřeno → napůl → otevřeno\" je jádro vzoru.\n";
echo "    Po vypršení intervalu jistič pustí JEDINÝ pokus. Když projde,\n";
echo "    zavírá; když ne, zase se otevře a čeká další interval.\n";
echo "    Nezkouší to znovu naplno — tím by bránu dorazil.\n\n";

$reopened = count(array_filter(
    $breaker->transitions(),
    static fn (array $t): bool => $t['from'] === CircuitBreaker::HALF_OPEN && $t['to'] === CircuitBreaker::OPEN,
));

printf("    %s%d\n", pad('zkušebních pokusů, které selhaly', 36), $reopened);
printf("    %s%s\n\n", pad('konečný stav', 36), $breaker->state());

// --- 4. Čím se tím dá ublížit ----------------------------------------------

echo "4. Čím se tím dá ublížit\n\n";

printf("    %s%s\n", pad('práh příliš nízký', 30), 'jistič se otevře při běžném zakolísání');
printf("    %s%s\n", pad('práh příliš vysoký', 30), 'otevře se až potom, co už je pozdě');
printf("    %s%s\n", pad('interval příliš krátký', 30), 'zkušební pokusy bránu nenechají vstát');
printf("    %s%s\n\n", pad('interval příliš dlouhý', 30), 'brána už jede a ty ji pořád odmítáš');

echo "    A ta hlavní: jistič ti nevrátí odpověď. Přemění POMALOU\n";
echo "    chybu na RYCHLOU — a co s tou rychlou chybou uděláš, je\n";
echo "    rozhodnutí, které za tebe neudělá.\n";
