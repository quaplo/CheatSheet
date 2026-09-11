<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka dávkování.
 *
 * Čas je simulovaný, ne skutečný — demo tedy doběhne okamžitě
 * a při každém spuštění dá totéž.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Batcher.php';

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function trips(int $n): string
{
    return match (true) {
        $n === 1 => '1 kolo',
        $n < 5 => $n . ' kola',
        default => $n . ' kol',
    };
}

const MAX_SIZE = 500;
const MAX_SECONDS = 3.0;

/**
 * Provoz, který do systému přitéká.
 *
 * Import vysype 2 000 událostí během chvilky, pak se systém vrátí
 * k běžnému provozu — pár událostí za minutu.
 *
 * @return list<array{0: string, 1: float}> dvojice [zpráva, čas]
 */
function traffic(): array
{
    $events = [];
    $now = 0.0;

    // Import: 2 000 událostí, zhruba 400 za sekundu.
    for ($i = 1; $i <= 2000; ++$i) {
        $events[] = ['import-' . $i, $now];
        $now += 0.0025;
    }

    // Běžný provoz: 12 událostí, jedna za 20 sekund.
    for ($i = 1; $i <= 12; ++$i) {
        $now += 20.0;
        $events[] = ['provoz-' . $i, $now];
    }

    return $events;
}

echo "=== Dávkování (batching) ===\n\n";

printf("    Nastavení: dávka %d zpráv, časový limit %.0f s.\n", MAX_SIZE, MAX_SECONDS);
echo "    Vyprázdní se podle toho, co nastane dřív.\n\n";

// --- 1. Kolik kol ubude ---------------------------------------------------

echo "1. Kolik kol s cílovým systémem ubude\n\n";

$processed = 0;
$batcher = new Batcher(MAX_SIZE, MAX_SECONDS, static function (array $batch) use (&$processed): void {
    $processed += count($batch);
});

$events = traffic();
$clock = 0.0;

foreach ($events as [$message, $at]) {
    // Časová smyčka běží nezávisle na provozu — doženeme ji sem.
    while ($clock + 0.5 <= $at) {
        $clock += 0.5;
        $batcher->tick($clock);
    }

    $clock = $at;
    $batcher->add($message, $clock);
}

// Doběh: smyčka tiká dál i potom, co provoz ustal.
for ($i = 0; $i < 20; ++$i) {
    $clock += 0.5;
    $batcher->tick($clock);
}

$batcher->shutdown($clock);

$flushes = $batcher->flushes();

printf("    %s%s\n", pad('událostí celkem', 30), (string) count($events));
printf("    %s%s\n", pad('bez dávkování', 30), trips(count($events)));
printf("    %s%s\n", pad('s dávkováním', 30), trips(count($flushes)));
printf("    %s%s\n\n", pad('zpracováno událostí', 30), $processed === count($events) ? 'všechny' : 'CHYBÍ ' . (count($events) - $processed));

printf(
    "    Kol ubylo %.1f×. To je celý zisk — a je to jediné číslo,\n    které se dá spočítat bez znalosti tvojí infrastruktury.\n\n",
    count($events) / count($flushes),
);

// --- 2. Která podmínka to spustila ----------------------------------------

echo "2. Která z těch dvou podmínek to spustila\n\n";

$byReason = [];

foreach ($flushes as $flush) {
    $byReason[$flush['reason']][] = $flush['size'];
}

printf("    %s%s%s\n", pad('důvod', 16), pad('vyprázdnění', 16), 'průměrná dávka');

foreach ($byReason as $reason => $sizes) {
    printf(
        "    %s%s%s\n",
        pad($reason, 16),
        pad((string) count($sizes), 16),
        (string) (int) round(array_sum($sizes) / count($sizes)),
    );
}

echo "\n    Při importu se plní dávka a spouští ji VELIKOST. V běžném\n";
echo "    provozu by zpráva čekala na dalších 499, a proto ji pouští\n";
echo "    ČAS. Každá z těch dvou podmínek pokrývá jiný režim a ani\n";
echo "    jedna sama o sobě nestačí.\n\n";

// --- 3. Za co se platí ----------------------------------------------------

echo "3. Za co se platí\n\n";

const TICK_SECONDS = 0.5;

printf("    %s%s%s\n", pad('důvod vyprázdnění', 20), pad('nejdelší čekání', 20), 'čím je omezené');

foreach (['velikost', 'čas', 'ukončení'] as $reason) {
    $waits = array_map(
        static fn (array $flush): float => $flush['waited'],
        array_filter($flushes, static fn (array $flush): bool => $flush['reason'] === $reason),
    );

    if ($waits === []) {
        continue;
    }

    printf(
        "    %s%s%s\n",
        pad($reason, 20),
        pad(sprintf('%.2f s', max($waits)), 20),
        match ($reason) {
            'velikost' => 'rychlostí provozu',
            'čas' => sprintf('limitem %.1f s + tikem %.1f s', MAX_SECONDS, TICK_SECONDS),
            'ukončení' => 'ničím — je to poslední dávka',
        },
    );
}

echo "\n    Dávkování mění propustnost za latenci a časový limit je cena\n";
echo "    toho obchodu: nastavuješ, jak dlouho smí nejpomalejší zpráva\n";
echo "    čekat.\n\n";

printf(
    "    Pozor ale na prostřední řádek: skutečná horní mez není %.1f s,\n    ale %.1f s. Časová smyčka kontroluje buffer jen každých %.1f s,\n    takže se limit a perioda smyčky SČÍTAJÍ. Kdo si nastaví limit\n    3 s a měří 3,4, nemá chybu — má jen smyčku, o které zapomněl.\n\n",
    MAX_SECONDS,
    MAX_SECONDS + TICK_SECONDS,
    TICK_SECONDS,
);

// --- 4. Past, která se pozná až při pádu ----------------------------------

echo "4. Past, která se pozná až při pádu\n\n";

$delivered = 0;
$crashed = new Batcher(MAX_SIZE, MAX_SECONDS, static function (array $batch) use (&$delivered): void {
    $delivered += count($batch);
});

foreach (array_slice(traffic(), 0, 120) as [$message, $at]) {
    $crashed->add($message, $at);
}

printf("    %s%s\n", pad('přidáno do bufferu', 34), '120');
printf("    %s%s\n", pad('předáno dál', 34), (string) $delivered);
printf("    %s%s\n", pad('čeká na vyprázdnění', 34), (string) $crashed->pending());
printf("    %s%s\n\n", pad('když teď proces spadne', 34), $crashed->pending() . ' zpráv je pryč');

echo "    Buffer je v paměti. Nasazení, OOM killer i obyčejný restart\n";
echo "    ho vezmou s sebou — a nikde se to neprojeví, protože zprávy\n";
echo "    do systému dorazily a byly přijaty.\n\n";

echo "    Proto k dávkování patří dvě věci, které nejsou volitelné:\n";
echo "      · vyprázdnění při ukončení (SIGTERM), a\n";
echo "      · zdroj, ze kterého jde nedoručené vyčíst znovu.\n";
