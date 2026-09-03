<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Iterator.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/OrderItem.php';
require __DIR__ . '/OrderItems.php';
require __DIR__ . '/LargeCatalog.php';
require __DIR__ . '/CategoryNode.php';

function money(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

function mb(int $bytes): string
{
    return number_format($bytes / 1048576, 1, ',', ' ') . ' MB';
}

echo "=== Iterator ===\n\n";

// --- 1. Struktura schovaná za foreach -------------------------------------

echo "1. Volající neví, co je uvnitř\n";

$items = new OrderItems();
$items->add(new OrderItem('MON-27', 799000));
$items->add(new OrderItem('KLA-01', 129000));
$items->add(new OrderItem('MYS-01', 45000));

echo "\n";

foreach ($items as $sku => $item) {
    printf("        %s  %s\n", mb_str_pad($sku, 10), money($item->priceInCents));
}

printf("\n    count(): %d\n", count($items));
echo "\n    Uvnitř je pole indexované podle SKU. Kdyby se z toho zítra\n";
echo "    stal SplHeap nebo databázový kurzor, tenhle foreach zůstane.\n";

// --- 2. Generátor jako filtr ----------------------------------------------

echo "\n2. Filtrovaný průchod bez mezipole\n\n";

foreach ($items->moreExpensiveThan(100000) as $sku => $item) {
    printf("        %s  %s\n", mb_str_pad($sku, 10), money($item->priceInCents));
}

echo "\n    moreExpensiveThan() nevrací pole — vrací generátor.\n";
echo "    Nad třemi položkami je to jedno. Nad milionem ne.\n";

// --- 3. Paměť: pole proti generátoru --------------------------------------

echo "\n3. Kolik to stojí paměti\n\n";

$count = 100_000;

gc_collect_cycles();
$before = memory_get_usage(true);
$array = LargeCatalog::asArray($count);
$sum = 0;
foreach ($array as $row) {
    $sum += $row['price'];
}
$arrayMemory = memory_get_usage(true) - $before;
unset($array);

gc_collect_cycles();
$before = memory_get_usage(true);
$sumGen = 0;
foreach (LargeCatalog::asGenerator($count) as $row) {
    $sumGen += $row['price'];
}
$generatorMemory = max(memory_get_usage(true) - $before, 0);

printf("    %s %12s %16s\n", mb_str_pad('', 16), 'paměť', 'součet cen');
printf("    %s %12s %16s\n", mb_str_pad('pole', 16), mb($arrayMemory), money($sum));
printf("    %s %12s %16s\n", mb_str_pad('generátor', 16), mb($generatorMemory), money($sumGen));

printf("\n    %s položek, výsledek stejný.\n", number_format($count, 0, ',', ' '));
printf("    Pole si drží všechny naráz, generátor vždycky jen jednu.\n");

// A teď to, co s polem nejde vůbec.
$huge = 3_000_000;
$projected = (int) ($arrayMemory / $count * $huge);

printf("\n    Kdybych chtěl %s položek:\n", number_format($huge, 0, ',', ' '));
printf("        pole by potřebovalo    ~%s   (odhad z naměřené ceny na položku)\n", mb($projected));
printf("        limit tohohle procesu:  %s\n", ini_get('memory_limit'));

gc_collect_cycles();
$before = memory_get_usage(true);
$started = hrtime(true);
$sumHuge = 0;
foreach (LargeCatalog::asGenerator($huge) as $row) {
    $sumHuge += $row['price'];
}
printf("        generátor to zvládl:    %s za %.1f s, součet %s\n",
    mb(max(memory_get_usage(true) - $before, 0)),
    (hrtime(true) - $started) / 1e9,
    money($sumHuge),
);

echo "\n    Tohle není optimalizace, ale rozdíl mezi „projde to\"\n";
echo "    a „spadne to na paměti\".\n";

// --- 4. Líné vyhodnocení: nekonečná řada ----------------------------------

echo "\n4. Co jde jen líně\n\n";

$taken = [];

foreach (LargeCatalog::infinitePrices() as $price) {
    if ($price > 12000) {
        break;
    }

    $taken[] = money($price);
}

printf("        infinitePrices() je while (true) — a přesto to doběhlo\n");
printf("        vzato: %s\n", implode(', ', $taken));

echo "\n    Generátor vyrobí hodnotu, až když si o ni někdo řekne.\n";
echo "    Nekonečná řada je tím pádem legitimní věc, ne chyba.\n";

// --- 5. Průchod stromem ---------------------------------------------------

echo "\n5. Strom, jehož tvar volající nezná\n\n";

$catalog = (new CategoryNode('Elektronika'))
    ->add(
        (new CategoryNode('Monitory'))
            ->add(new CategoryNode('Herní'))
            ->add(new CategoryNode('Kancelářské')),
    )
    ->add(
        (new CategoryNode('Periferie'))
            ->add(new CategoryNode('Klávesnice')),
    );

$names = [];

foreach ($catalog as $node) {
    $names[] = $node->name;
}

printf("        %s\n", implode(' → ', $names));
printf("\n        uzlů: %d, a foreach o hloubce stromu neví nic\n", count($names));

echo "\n    Uvnitř je `yield from` — rekurze v generátoru. Volající\n";
echo "    dostane plochou posloupnost a strom ho nezajímá.\n";

// --- 6. Past, na kterou narazí každý --------------------------------------

echo "\n6. Generátor jde projít jen jednou\n";

$generator = LargeCatalog::asGenerator(3);

$first = iterator_to_array($generator, preserve_keys: false);
printf("\n    první průchod:  %d položek\n", count($first));

try {
    foreach ($generator as $row) {
        // sem se nedostaneme
    }
} catch (Exception $e) {
    printf("    druhý průchod:  %s\n", $e->getMessage());
}

echo "\n    Pole projdeš kolikrát chceš, generátor jednou. Když to\n";
echo "    volající potřebuje víckrát, vrať pole — nebo mu dej\n";
echo "    továrnu, která generátor vyrobí znovu.\n";
