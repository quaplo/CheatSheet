<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka opakování.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Strategies.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function calls(int $n): string
{
    return match (true) {
        $n === 1 => '1 volání',
        $n < 5 => $n . ' volání',
        default => $n . ' volání',
    };
}

echo "=== Retry (opakování) ===\n\n";

// --- 1. Kdy to pomůže a kdy je to jen zátěž -------------------------------

echo "1. Opakování pomůže jen na jeden druh chyby\n\n";

/**
 * @param callable(int): bool $service vrátí true, když volání projde
 * @return array{ok: bool, calls: int}
 */
function withRetry(callable $service, int $maxAttempts): array
{
    for ($attempt = 1; $attempt <= $maxAttempts; ++$attempt) {
        if ($service($attempt)) {
            return ['ok' => true, 'calls' => $attempt];
        }
    }

    return ['ok' => false, 'calls' => $maxAttempts];
}

$cases = [
    'přechodná (timeout, 503)' => static fn (int $attempt): bool => $attempt >= 3,
    'trvalá (400, neplatný token)' => static fn (int $attempt): bool => false,
];

printf("    %s%s%s\n", pad('druh chyby', 32), pad('výsledek', 14), 'volání na službu');

foreach ($cases as $label => $service) {
    $result = withRetry($service, 5);

    printf(
        "    %s%s%s\n",
        pad($label, 32),
        pad($result['ok'] ? 'prošlo' : 'selhalo', 14),
        calls($result['calls']),
    );
}

echo "\n    Druhý řádek je celé opakování naruby: pět volání, nula\n";
echo "    užitku, a přitom jsi službě přidal pětinásobek zátěže.\n";
echo "    Opakovat má smysl jen to, co MŮŽE příště dopadnout jinak.\n\n";

// --- 2. Násobení napříč vrstvami ------------------------------------------

echo "2. Nejdražší chyba: opakování na každé vrstvě\n\n";

echo "    Každá vrstva si to „pro jistotu\" zkusí třikrát.\n\n";

printf("    %s%s%s\n", pad('vrstev, které opakují', 26), pad('volání na spodní službu', 26), 'z jednoho požadavku');

$attempts = 3;

foreach (range(1, 5) as $layers) {
    printf(
        "    %s%s%s\n",
        pad((string) $layers, 26),
        pad(number_format($attempts ** $layers, 0, ',', ' '), 26),
        '1',
    );
}

echo "\n    Tři vrstvy po třech pokusech je 27 volání z jednoho kliknutí.\n";
echo "    Nikdo z těch tří nedělá nic nerozumného — a dohromady je to\n";
echo "    útok na vlastní infrastrukturu.\n\n";

echo "    Proto se opakuje na JEDNÉ vrstvě, a to na té, která ví, jestli\n";
echo "    je operace bezpečné zopakovat.\n\n";

// --- 3. Proč nestačí exponenciální backoff --------------------------------

echo "3. Sto klientů spadne ve stejnou vteřinu\n\n";

mt_srand(42);
$rand = static fn (): float => mt_rand() / mt_getrandmax();

$clients = 100;
$bucket = 0.5;
$strategies = [
    'pevná prodleva' => Strategies::fixed(...),
    'exponenciální' => Strategies::exponential(...),
    'exponenciální + jitter' => Strategies::fullJitter(...),
];

printf("    Sleduje se 4. pokus a čas se dělí do košů po %.1f s.\n\n", $bucket);

printf(
    "    %s%s%s%s\n",
    pad('strategie', 26),
    pad('prodleva', 16),
    pad('nejhorší koš', 16),
    'košů s provozem',
);

foreach ($strategies as $label => $strategy) {
    $buckets = [];
    $delays = [];

    for ($client = 0; $client < $clients; ++$client) {
        $delay = $strategy(4, $rand);
        $delays[] = $delay;
        $key = (int) floor($delay / $bucket);
        $buckets[$key] = ($buckets[$key] ?? 0) + 1;
    }

    printf(
        "    %s%s%s%s\n",
        pad($label, 26),
        pad(
            min($delays) === max($delays)
                ? sprintf('%.1f s', min($delays))
                : sprintf('%.1f–%.1f s', min($delays), max($delays)),
            16,
        ),
        pad(max($buckets) . ' z ' . $clients, 16),
        (string) count($buckets),
    );
}

echo "\n    Exponenciální backoff sám o sobě NEPOMÁHÁ: prodleva je sice\n";
echo "    delší, ale pro všechny stejná, takže vlna se jen posune.\n";
echo "    Teprve náhoda je rozprostře.\n\n";

echo "    „No jitter keeps every client aligned; full jitter spreads\n";
echo "     them uniformly.\"      — Marc Brooker, AWS, 2015\n\n";

// --- 4. Kolik to celkem stojí ---------------------------------------------

echo "4. Kolik zátěže se tím přidá\n\n";

$outageSeconds = 60;
$requestsPerSecond = 20;
$maxAttempts = 3;

printf("    %s%s\n", pad('výpadek trvá', 30), $outageSeconds . ' s');
printf("    %s%s\n", pad('požadavků za vteřinu', 30), (string) $requestsPerSecond);
printf("    %s%s\n", pad('bez opakování', 30), calls($outageSeconds * $requestsPerSecond));
printf(
    "    %s%s\n\n",
    pad('s opakováním 3×', 30),
    calls($outageSeconds * $requestsPerSecond * $maxAttempts),
);

echo "    Služba, která se topí, dostane během výpadku TROJNÁSOBEK\n";
echo "    provozu. Opakování je tedy nástroj, který zhoršuje přesně\n";
echo "    tu situaci, kvůli které ho používáš — dokud se neomezí\n";
echo "    počtem pokusů a nespojí s jističem.\n";
