<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternů Core Domain a Generic Subdomains.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Subdomain.php';
require __DIR__ . '/Classification.php';
require __DIR__ . '/Classifier.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function bar(float $percent, int $width = 24): string
{
    $filled = (int) round($percent / 100 * $width);

    return str_repeat('█', $filled) . str_repeat('░', $width - $filled);
}

echo "=== Core Domain a Generic Subdomains ===\n\n";

/**
 * E-shop, který se živí tím, že umí chytře doporučovat zboží
 * a počítat dynamické ceny. Všechno ostatní jen musí fungovat.
 */
$subdomains = [
    new Subdomain('Doporučování zboží', differentiates: true,  availableOffTheShelf: true,  sameEverywhere: false, effortInDays: 40),
    new Subdomain('Dynamické ceny',     differentiates: true,  availableOffTheShelf: false, sameEverywhere: false, effortInDays: 25),
    new Subdomain('Katalog produktů',   differentiates: false, availableOffTheShelf: false, sameEverywhere: false, effortInDays: 60),
    new Subdomain('Objednávkový proces',differentiates: false, availableOffTheShelf: false, sameEverywhere: false, effortInDays: 55),
    new Subdomain('Platby',             differentiates: false, availableOffTheShelf: true,  sameEverywhere: true,  effortInDays: 45),
    new Subdomain('Fakturace a DPH',    differentiates: false, availableOffTheShelf: true,  sameEverywhere: true,  effortInDays: 35),
    new Subdomain('Odesílání e-mailů',  differentiates: false, availableOffTheShelf: true,  sameEverywhere: true,  effortInDays: 20),
    new Subdomain('Správa uživatelů',   differentiates: false, availableOffTheShelf: true,  sameEverywhere: true,  effortInDays: 30),
];

$classifier = new Classifier();

// --- 1. Klasifikace --------------------------------------------------------

echo "1. Co je jádro a co ne\n\n";

printf("    %s%s%s\n", pad('Podoblast', 24), pad('Klasifikace', 24), 'Co s tím');

$byClass = [];

foreach ($subdomains as $subdomain) {
    $class = $classifier->classify($subdomain);
    $byClass[$class->value][] = $subdomain;

    printf(
        "    %s%s%s\n",
        pad($subdomain->name, 24),
        pad($class->value, 24),
        $class->recommendation(),
    );
}

echo "\n    Všimni si prvního řádku: doporučování zboží JDE koupit,\n";
echo "    a přesto je to jádro. Kdo si koupí to, čím se má lišit,\n";
echo "    přestane se lišit.\n\n";

// --- 2. Kam teče úsilí -----------------------------------------------------

echo "2. Kam teče úsilí\n\n";

$total = array_sum(array_map(static fn (Subdomain $s): int => $s->effortInDays, $subdomains));

foreach (Classification::cases() as $class) {
    $group = $byClass[$class->value] ?? [];
    $days = array_sum(array_map(static fn (Subdomain $s): int => $s->effortInDays, $group));
    $percent = $days / $total * 100;

    printf(
        "    %s%s %5.1f %%   %3d dní\n",
        pad($class->value, 24),
        bar($percent),
        $percent,
        $days,
    );
}

$coreDays = array_sum(array_map(
    static fn (Subdomain $s): int => $s->effortInDays,
    $byClass[Classification::Core->value] ?? [],
));

printf("\n    do jádra jde %.0f %% úsilí, do zbytku %.0f %%\n\n", $coreDays / $total * 100, 100 - $coreDays / $total * 100);

echo "    Tohle je celá diagnóza. Firma, která se živí doporučováním\n";
echo "    a cenotvorbou, do nich dává pětinu času — a čtyři pětiny\n";
echo "    do věcí, které dělá stejně jako všichni ostatní.\n\n";

// --- 3. Co by se dalo získat zpátky ---------------------------------------

echo "3. Co by uvolnilo vytěsnění generických podoblastí\n\n";

$genericDays = array_sum(array_map(
    static fn (Subdomain $s): int => $s->effortInDays,
    $byClass[Classification::Generic->value] ?? [],
));

printf("    %s%s\n", pad('', 24), pad('dní', 10) . 'podíl');
printf("    %s%s%.0f %%\n", pad('generické podoblasti', 24), pad((string) $genericDays, 10), $genericDays / $total * 100);

foreach ($byClass[Classification::Generic->value] ?? [] as $s) {
    printf("        %s%d dní\n", pad($s->name, 24), $s->effortInDays);
}

printf("\n    kdyby se polovina z toho koupila:   %d dní zpět do jádra\n", (int) ($genericDays / 2));
printf("    jádro by pak mělo:                  %.0f %% místo %.0f %%\n\n",
    ($coreDays + $genericDays / 2) / $total * 100,
    $coreDays / $total * 100,
);

echo "    Evans: „Also consider off-the-shelf solutions or published\n";
echo "    models for these generic subdomains.\"\n\n";

// --- 4. Kdo na čem pracuje -------------------------------------------------

echo "4. Kdo na tom má dělat\n\n";

foreach (Classification::cases() as $class) {
    printf("    %s%s\n", pad($class->value, 24), $class->staffing());
}

echo "\n    Evans upozorňuje na past: nejlepší lidé přirozeně tíhnou\n";
echo "    k technické infrastruktuře a k dobře ohraničeným úlohám,\n";
echo "    které jdou pochopit bez doménové znalosti — tedy přesně\n";
echo "    k tomu, co jádrem NENÍ.\n\n";

// --- 5. Test na jádro ------------------------------------------------------

echo "5. Rychlý test, jestli je něco jádro\n\n";

$questions = [
    'Kdyby to dělal konkurent stejně dobře, ztratíme výhodu?' => 'ano → jádro',
    'Dá se to koupit, aniž bychom o něco přišli?'             => 'ano → generické',
    'Rozumí tomu doménový expert líp než programátor?'        => 'ano → spíš jádro',
    'Dělala by to jiná firma v jiném oboru stejně?'           => 'ano → generické',
    'Museli bychom to napsat, i kdyby produkt dělal něco úplně jiného?' => 'ano → generické',
];

foreach ($questions as $question => $answer) {
    printf("    %s%s\n", pad($question, 66), $answer);
}

echo "\n    Žádná z otázek není technická. Klasifikace se nedá odvodit\n";
echo "    z kódu — musí ji udělat někdo, kdo ví, čím firma vydělává.\n";
