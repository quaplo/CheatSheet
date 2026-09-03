<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Composite.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/CatalogNode.php';
require __DIR__ . '/Product.php';
require __DIR__ . '/Category.php';

function money(?int $cents): string
{
    return $cents === null ? '—' : number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

/**
 * Klientský kód. Bere `CatalogNode` — a je mu jedno, co dostane.
 *
 * Tahle funkce je celý důkaz patternu: zavolá se na produkt i na
 * kořen stromu a v obou případech dá smysl.
 */
function summarize(CatalogNode $node): void
{
    printf("        %s  produktů: %d, nejlevnější: %s\n",
        mb_str_pad($node->name(), 22),
        $node->productCount(),
        money($node->lowestPriceInCents()),
    );
}

$catalog = (new Category('Elektronika'))
    ->add(
        (new Category('Monitory'))
            ->add(new Product('Monitor 24"', 449000))
            ->add(new Product('Monitor 27"', 799000))
            ->add(new Product('Monitor 32" 4K', 1890000, isAvailable: false)),
    )
    ->add(
        (new Category('Periferie'))
            ->add(
                (new Category('Klávesnice'))
                    ->add(new Product('Mechanická', 289000))
                    ->add(new Product('Bezdrátová', 129000)),
            )
            ->add(new Product('Myš', 45000))
            ->add(new Product('Podložka', 19000)),
    )
    ->add(new Product('Prodloužená záruka', 99000));

echo "=== Composite ===\n\n";

// --- 1. Strom -------------------------------------------------------------

echo "1. Strom kategorií a produktů\n\n";
echo $catalog->render(1);

// --- 2. List a uzel jsou zaměnitelné --------------------------------------

echo "\n2. Táž funkce na produkt i na celou kategorii\n\n";

$monitors = $catalog->children()[0];
$leaf = new Product('Myš', 45000);

summarize($leaf);
summarize($monitors);
summarize($catalog);

echo "\n    Funkce summarize() nemá jediné `if` o typu. Neví, jestli\n";
echo "    dostala produkt, kategorii, nebo kořen celého katalogu.\n";

// --- 3. Rekurze bez podmínek ----------------------------------------------

echo "\n3. Jak to funguje uvnitř\n\n";
echo "    Category::productCount():\n";
echo "        sečti productCount() všech potomků\n\n";
echo "    Product::productCount():\n";
echo "        vrať 1\n\n";
echo "    To je všechno. Kategorie se NEPTÁ, jestli je potomek list\n";
echo "    nebo uzel — zeptá se ho na totéž, co by se zeptal kdokoli.\n";
echo "    Rekurze vznikne sama a nikde není `instanceof`.\n";

// --- 4. Hloubka nikoho nezajímá -------------------------------------------

echo "\n4. Strom se může větvit libovolně hluboko\n\n";

$depths = [];
$measure = static function (CatalogNode $node, int $depth) use (&$measure, &$depths): void {
    $depths[] = $depth;

    if ($node instanceof Category) {
        foreach ($node->children() as $child) {
            $measure($child, $depth + 1);
        }
    }
};
$measure($catalog, 0);

printf("    uzlů celkem:      %d\n", count($depths));
printf("    největší hloubka: %d\n", max($depths));
printf("    productCount():   %d\n", $catalog->productCount());

echo "\n    Kdyby přibylo pět dalších úrovní, žádná z tříd se nezmění.\n";
echo "    Přibudou jen instance.\n";

// --- 5. Kam se to promítne ------------------------------------------------

echo "\n5. Kde tenhle strom v katalogu už je\n\n";
echo "    Specification: AndSpecification drží dvě specifikace\n";
echo "                   a sama je specifikací. To JE Composite.\n\n";
echo "    Aggregate:     objednávka drží položky, ale ne rekurzivně —\n";
echo "                   proto to Composite NENÍ.\n\n";
echo "    Rozdíl je v rekurzi: Composite znamená, že uzel obsahuje\n";
echo "    tentýž typ, jakým sám je. Bez toho je to obyčejná kolekce.\n";
