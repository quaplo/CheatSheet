<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Highlighted Core.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/CoreDomain.php';
require __DIR__ . '/Recommendation/RecommendationEngine.php';
require __DIR__ . '/Recommendation/SimilarityScore.php';
require __DIR__ . '/Recommendation/ViewHistory.php';
require __DIR__ . '/Pricing/DynamicPrice.php';
require __DIR__ . '/Pricing/PriceFloor.php';
require __DIR__ . '/Catalog/Product.php';
require __DIR__ . '/Catalog/Category.php';
require __DIR__ . '/Invoicing/Invoice.php';
require __DIR__ . '/Invoicing/VatRate.php';
require __DIR__ . '/Catalog/ProductImage.php';
require __DIR__ . '/Ordering/Order.php';
require __DIR__ . '/Ordering/OrderLine.php';
require __DIR__ . '/Notification/EmailSender.php';
require __DIR__ . '/Notification/Template.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/**
 * Najde všechny třídy označené jako jádro.
 *
 * Tohle je celý přínos druhé formy zvýrazněného jádra: seznam se
 * nepíše ručně, čte se z modelu — takže nemůže zastarat.
 *
 * @return array<string, string> třída => proč
 */
function findCore(): array
{
    $core = [];

    foreach (get_declared_classes() as $class) {
        $reflection = new ReflectionClass($class);

        if ($reflection->isInternal()) {
            continue;
        }

        foreach ($reflection->getAttributes(CoreDomain::class) as $attribute) {
            $core[$class] = $attribute->newInstance()->why;
        }
    }

    return $core;
}

/** @return list<string> všechny třídy z demo modelu */
function allModelClasses(): array
{
    return array_values(array_filter(
        get_declared_classes(),
        static function (string $class): bool {
            $r = new ReflectionClass($class);

            return !$r->isInternal()
                && $class !== 'CoreDomain'
                && str_starts_with((string) $r->getFileName(), __DIR__);
        },
    ));
}

echo "=== Highlighted Core ===\n\n";

// --- 1. Seznam jádra, který se nepíše ručně -------------------------------

echo "1. Co je jádro — přečtené z modelu\n\n";

$core = findCore();

foreach ($core as $class => $why) {
    printf("    %s\n        %s\n", $class, $why);
}

// --- 2. Kolik z modelu je jádro -------------------------------------------

echo "\n2. Jak velké je jádro\n\n";

$all = allModelClasses();
$share = count($core) / count($all) * 100;

printf("    %s%d\n", pad('tříd v modelu', 26), count($all));
printf("    %s%d\n", pad('z toho jádro', 26), count($core));
printf("    %s%.0f %%\n\n", pad('podíl jádra', 26), $share);

echo $share <= 40
    ? "    Jádro je malé — přesně jak Evans žádá: „make the core small\".\n\n"
    : "    Jádro je podezřele velké. Když je jádrem půlka modelu,\n    značka přestává nést informaci.\n\n";

// --- 3. Kudy vede hranice uvnitř modulu -----------------------------------

echo "3. Hranice nevede podle složek\n\n";

$byNamespace = [];

foreach ($all as $class) {
    $ns = str_contains($class, '\\') ? substr($class, 0, strrpos($class, '\\')) : '(kořen)';
    $byNamespace[$ns][] = ['class' => $class, 'core' => isset($core[$class])];
}

ksort($byNamespace);

foreach ($byNamespace as $ns => $classes) {
    $coreCount = count(array_filter($classes, static fn (array $c): bool => $c['core']));
    printf("    %s%d z %d v jádru\n", pad($ns, 20), $coreCount, count($classes));

    foreach ($classes as $c) {
        printf("        %s%s\n", $c['core'] ? '● ' : '○ ', $c['class']);
    }
}

echo "\n    Modul Recommendation má tři třídy, ale jen dvě jsou jádro.\n";
echo "    ViewHistory je obyčejný sběr dat — vypadal by stejně\n";
echo "    v jakémkoli e-shopu. Značka to rozliší, složka ne.\n\n";

// --- 4. Indikátor významnosti změny ---------------------------------------

echo "4. K čemu to je při změně\n\n";

$changes = [
    'Recommendation\SimilarityScore' => 'změna vzorce podobnosti',
    'Recommendation\ViewHistory' => 'přidání indexu do historie',
    'Invoicing\VatRate' => 'nová sazba DPH',
    'Pricing\PriceFloor' => 'změna spodní hranice ceny',
];

printf("    %s%s%s\n", pad('Změna v', 34), pad('jádro?', 10), 'jak s ní naložit');

foreach ($changes as $class => $description) {
    $isCore = isset($core[$class]);

    printf(
        "    %s%s%s\n",
        pad($description, 34),
        pad($isCore ? 'ano' : 'ne', 10),
        $isCore ? 'projednat s týmem, dát vědět' : 'integrovat bez konzultace',
    );
}

echo "\n    Tohle je nejpraktičtější důsledek celého vzoru. Evans:\n";
echo "    změna, která zasáhne jádro, vyžaduje projednání a oznámení;\n";
echo "    změny mimo jádro se integrují bez konzultace — a tým tak má\n";
echo "    „the full autonomy that most Agile processes suggest\".\n\n";

// --- 5. Co by musel udělat člověk bez značek ------------------------------

echo "5. Kolik práce to ušetří\n\n";

printf("    %s%s\n", pad('', 30), pad('se značkami', 18) . 'bez nich');
printf("    %s%s%s\n", pad('zjistit, co je jádro', 30), pad('jeden příkaz', 18), 'přečíst model');
printf("    %s%s%s\n", pad('udržet seznam aktuální', 30), pad('sám od sebe', 18), 'ruční revize');
printf("    %s%s%s\n", pad('shoda napříč týmem', 30), pad('vynucená', 18), 'záleží na výkladu');

echo "\n    Evans o té ruční variantě: „The mental labor of constantly\n";
echo "    filtering the model to identify the key parts absorbs\n";
echo "    concentration better spent on design thinking.\"\n";
