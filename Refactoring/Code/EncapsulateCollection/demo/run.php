<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka refaktoringu Encapsulate Collection.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/OrderItem.php';
require __DIR__ . '/Before/Order.php';
require __DIR__ . '/Before/Usage.php';
require __DIR__ . '/After/OrderItems.php';
require __DIR__ . '/After/Order.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function czk(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}

/** Kolikrát se v souboru volá array_* nad kolekcí. */
function arrayFunctionCalls(string $file): int
{
    $count = 0;

    foreach (token_get_all(file_get_contents($file)) as $token) {
        if (is_array($token) && $token[0] === T_STRING && str_starts_with($token[1], 'array_')) {
            ++$count;
        }
    }

    return $count;
}

echo "=== Encapsulate Collection ===\n\n";

$before = new Before\Order('2026/001');
$before->addItem(new OrderItem('MON-27', 799000, 1));
$before->addItem(new OrderItem('KLA-01', 249000, 2));

// --- 1. V PHP je to jinak než v Javě ---------------------------------------

echo "1. Pozor: v PHP vrácené pole JE kopie\n\n";

$stolen = $before->items();
$stolen[] = new OrderItem('PODVRH', 1, 1);

printf("    %s%d\n", pad('položek v objednávce', 30), count($before->items()));
printf("    %s%d\n\n", pad('po přidání do vráceného pole', 30), count($before->items()));

echo "    Fowlerův příklad je v JavaScriptu, kde by se položka přidala.\n";
echo "    V PHP se pole při vrácení kopíruje, takže tenhle útok neprojde.\n";
echo "    Kdo tedy refaktoring dělá kvůli tomuhle, dělá ho v PHP zbytečně.\n\n";

// --- 2. Skutečná díra ------------------------------------------------------

echo "2. Skutečná díra: objekty v poli jsou reference\n\n";

$original = $before->items()[0]->priceInCents();
$before->items()[0]->changePrice(1);

printf("    %s%s\n", pad('cena první položky', 30), czk($original));
printf("    %s%s   ← změněno zvenčí\n", pad('po zásahu přes items()', 30), czk($before->items()[0]->priceInCents()));
printf("    %s%s\n\n", pad('součet objednávky', 30), czk(Before\Usage::total($before)));

echo "    Kopie pole ochrání pole, ne objekty v něm. Objednávka právě\n";
echo "    přišla o osm tisíc a nic o tom neví.\n\n";

// --- 3. Logika rozesetá po projektu ---------------------------------------

echo "3. Logika nad kolekcí je rozesetá\n\n";

$usageCalls = arrayFunctionCalls(__DIR__ . '/Before/Usage.php');
$usageMethods = count((new ReflectionClass(Before\Usage::class))->getMethods());

printf("    %s%d\n", pad('míst pracujících s polem', 30), $usageMethods);
printf("    %s%d\n\n", pad('volání array_* funkcí', 30), $usageCalls);

echo "    Každá z těch metod je v projektu jinde — kontroler, šablona,\n";
echo "    exportér, e-mail, report. Všechny dělají totéž nad týmž polem.\n\n";

// --- 4. Pravidla skupiny se nemají kde vynutit ----------------------------

echo "4. Pravidla skupiny nemá kdo hlídat\n\n";

$loose = new Before\Order('2026/002');

for ($i = 0; $i < 12; ++$i) {
    $loose->addItem(new OrderItem('SKU-' . $i, 10000, 1));
}

$loose->addItem(new OrderItem('SKU-0', 10000, 1));   // duplicitní SKU

printf("    %s%d\n", pad('položek (limit má být 10)', 30), count($loose->items()));
printf("    %s%d\n\n", pad('unikátních SKU', 30), count(array_unique(Before\Usage::skus($loose))));

echo "    Třináct položek při limitu deset a jedno SKU dvakrát.\n";
echo "    Nikdo to nezachytil, protože pravidlo nemělo kde být.\n\n";

// --- 5. Po refaktoringu ----------------------------------------------------

echo "5. Po refaktoringu: kolekce si hlídá sebe\n\n";

$after = new After\Order('2026/003');
$after->addItem(new OrderItem('MON-27', 799000, 1));
$after->addItem(new OrderItem('KLA-01', 249000, 2));

printf("    %s%s\n", pad('součet', 26), czk($after->items()->totalInCents()));
printf("    %s%d\n", pad('kusů', 26), $after->items()->pieceCount());
printf("    %s%d\n\n", pad('různých položek', 26), count($after->items()));

foreach ([
    'duplicitní SKU' => static fn () => $after->addItem(new OrderItem('MON-27', 799000, 1)),
    'překročení limitu' => static function () {
        $full = new After\Order('2026/004');

        for ($i = 0; $i < 11; ++$i) {
            $full->addItem(new OrderItem('SKU-' . $i, 1000, 1));
        }
    },
] as $label => $attempt) {
    try {
        $attempt();
        printf("    %s%s\n", pad($label, 26), 'prošlo — CHYBA');
    } catch (DomainException $e) {
        printf("    %s%s\n", pad($label, 26), $e->getMessage());
    }
}

echo "\n";

// --- 6. Co zmizelo z volajícího kódu --------------------------------------

echo "6. Co se stalo s tou rozesetou logikou\n\n";

printf("    %s%s%s\n", pad('', 30), pad('Before', 20), 'After');
printf("    %s%s%s\n", pad('míst s array_* nad kolekcí', 30), pad((string) $usageCalls, 20), '0');
printf("    %s%s%s\n", pad('metod mimo objednávku', 30), pad((string) $usageMethods, 20), '0');
printf("    %s%s%s\n", pad('kde jsou pravidla skupiny', 30), pad('nikde', 20), 'v kolekci');
printf("    %s%s%s\n\n", pad('jde projít foreach', 30), pad('ano', 20), 'ano');

echo "    Poslední řádek je důležitý: kolekce implementuje\n";
echo "    IteratorAggregate a Countable, takže foreach i count()\n";
echo "    fungují dál. Zapouzdření neznamená nepohodlí.\n\n";

$total = 0;

foreach ($after->items() as $item) {
    $total += $item->subtotalInCents();
}

printf("    foreach přes kolekci:   %s\n", czk($total));
printf("    count() nad kolekcí:    %d\n", count($after->items()));
