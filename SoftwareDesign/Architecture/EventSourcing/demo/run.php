<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka Event Sourcingu.
 *
 * Spuštění:  php run.php
 */

foreach (['Events', 'ShippingRules', 'Order', 'SnapshotOrder', 'LegacyItemAdded', 'Upcaster'] as $file) {
    require __DIR__ . '/' . $file . '.php';
}

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function money(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}

/** Týž příběh objednávky, zapsaný jako proud událostí. */
$stream = [
    new OrderPlaced('A-2026-118', 'Dlouhá 12, Brno', '2026-09-01'),
    new ItemAdded('KNIHA-1', 12900, 2, '2026-09-01'),
    new ItemAdded('KNIHA-7', 4900, 1, '2026-09-02'),
    new AddressChanged('Nádražní 5, Brno', '2026-09-03'),
    new OrderShipped('PPL', '2026-09-04'),
    new AddressChanged('Dlouhá 12, Brno', '2026-09-07'),
];

$rules2026 = new ShippingRules(freeFromInCents: 100000);

echo "=== Event Sourcing ===\n\n";

// --- 1. Co snímek neví -----------------------------------------------------

echo "1. Co se ztratí, když se ukládá jen stav\n\n";

$snapshot = new SnapshotOrder();
$snapshot->changeAddress('Dlouhá 12, Brno');
$snapshot->addItem(12900, 2);
$snapshot->addItem(4900, 1);
$snapshot->changeAddress('Nádražní 5, Brno');
$snapshot->ship('PPL');
$snapshot->changeAddress('Dlouhá 12, Brno');

$order = Order::replay($stream, $rules2026);

$questions = [
    'Jaká je dnes adresa?' => [
        $snapshot->shippingAddress,
        $order->shippingAddress(),
    ],
    'Kolikrát se adresa změnila?' => [
        null,
        (string) $order->countOf(AddressChanged::class),
    ],
    'Na jakou adresu se to odeslalo?' => [
        null,
        $order->addressWhen(OrderShipped::class, $rules2026),
    ],
    'Změnil ji zákazník až po odeslání?' => [
        null,
        $order->countOf(AddressChanged::class) > 1 ? 'ano' : 'ne',
    ],
];

printf("    %s%s%s\n", pad('otázka', 36), pad('ze snímku', 20), 'z proudu událostí');

$snapshotAnswers = 0;

foreach ($questions as $question => [$fromSnapshot, $fromStream]) {
    $snapshotAnswers += $fromSnapshot === null ? 0 : 1;

    printf(
        "    %s%s%s\n",
        pad($question, 36),
        pad($fromSnapshot ?? '—', 20),
        $fromStream,
    );
}

printf(
    "\n    %s%d ze %d           %d ze %d\n\n",
    pad('zodpovězeno', 36),
    $snapshotAnswers,
    count($questions),
    count($questions),
    count($questions),
);

echo "    Poslední řádek je ten, kvůli kterému se pro Event Sourcing\n";
echo "    lidé rozhodují: zákazník změnil adresu POTOM, co se zboží\n";
echo "    odeslalo. Ze snímku to nikdo nikdy nezjistí.\n\n";

// --- 2. Past první: přehrání dnešním kódem --------------------------------

echo "2. Past první — proud je neměnný, jeho výklad ne\n\n";

$rules2027 = new ShippingRules(freeFromInCents: 25000);

printf("    %s%s\n", pad('pravidlo v roce 2026', 34), 'doprava zdarma od 1 000 Kč');
printf("    %s%s\n\n", pad('pravidlo v roce 2027', 34), 'doprava zdarma od 250 Kč');

printf("    %s%s\n", pad('celkem přehráno pravidly 2026', 34), money($order->totalInCents($rules2026)));
printf("    %s%s\n\n", pad('celkem přehráno pravidly 2027', 34), money(Order::replay($stream, $rules2027)->totalInCents($rules2027)));

echo "    Tytéž události, jiná částka. Události se nezměnily — změnil\n";
echo "    se kód, který je vykládá. „Historie\" tedy není v proudu:\n";
echo "    je v proudu PLUS ve verzi kódu, kterým ho přehraješ.\n\n";

echo "    Proto se do událostí ukládají VÝSLEDKY rozhodnutí, ne vstupy\n";
echo "    pro jejich přepočítání. Kdyby v proudu bylo ShippingCharged\n";
echo "    s částkou, přehrání by ji nemohlo změnit.\n\n";

// --- 3. Past druhá: události starší než dnešní kód ------------------------

echo "3. Past druhá — události jsou navždy, i ty staré\n\n";

$oldStream = [
    new OrderPlaced('A-2024-003', 'Dlouhá 12, Brno', '2024-05-01'),
    new LegacyItemAdded('KNIHA-1', 12900, '2024-05-01'),
];

$rawTotal = Order::replay($oldStream, $rules2026)->totalInCents($rules2026);
$upcastTotal = Order::replay(Upcaster::upcast($oldStream), $rules2026)->totalInCents($rules2026);

printf(
    "    %s%s\n",
    pad('proud z roku 2024 bez převodu', 34),
    money($rawTotal) . ($rawTotal < $upcastTotal ? '  ← položka se vůbec nezapočítala' : ''),
);
printf("    %s%s\n\n", pad('týž proud přes upcaster', 34), money($upcastTotal));

echo "    Nic nespadlo. Stará událost prostě propadla do default větve\n";
echo "    a v součtu chybí — to je na tom to nebezpečné.\n\n";

echo "    Starou událost nejde změnit migrací jako sloupec v tabulce —\n";
echo "    je to záznam o tom, co se stalo. Jediná cesta je převádět ji\n";
echo "    při čtení. A ten převod v kódu zůstane napořád.\n\n";

// --- 4. Co to stojí --------------------------------------------------------

echo "4. Co za to zaplatíš\n\n";

printf("    %s%s%s\n", pad('', 38), pad('snímek', 16), 'proud událostí');
$eventTypes = count(array_unique(array_map(static fn (Event $e): string => $e::class, $stream)));

printf("    %s%s%s\n", pad('typů událostí, které musíš udržovat', 38), pad('0', 16), (string) $eventTypes);
printf("    %s%s%s\n", pad('záznamů v úložišti', 38), pad('1', 16), (string) count($stream));
printf("    %s%s%s\n", pad('změna schématu', 38), pad('ALTER TABLE', 16), 'upcaster navždy');
printf("    %s%s%s\n", pad('smazání údaje (GDPR)', 38), pad('UPDATE', 16), 'nejde — jen šifrovat a zahodit klíč');
printf("    %s%s%s\n\n", pad('„jaký je stav?“', 38), pad('SELECT', 16), 'přehrát celý proud');

echo "    Poslední dva řádky jsou ty, které se v rozhodování\n";
echo "    podceňují nejčastěji.\n";
