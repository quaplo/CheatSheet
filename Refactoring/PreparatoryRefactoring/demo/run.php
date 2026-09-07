<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka přípravného refaktoringu.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Step0/Shipping.php';
require __DIR__ . '/Step1_Together/Shipping.php';
require __DIR__ . '/Step2_Prepared/ShippingMethod.php';
require __DIR__ . '/Step2_Prepared/FreeOverThreshold.php';
require __DIR__ . '/Step2_Prepared/Ppl.php';
require __DIR__ . '/Step2_Prepared/Dhl.php';
require __DIR__ . '/Step2_Prepared/Pickup.php';
require __DIR__ . '/Step2_Prepared/Shipping.php';
require __DIR__ . '/Step3_Feature/Balikovna.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function czk(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}

/** Řádky kódu bez komentářů a prázdných řádků. */
function codeLines(string ...$files): int
{
    $lines = 0;

    foreach ($files as $file) {
        foreach (token_get_all(file_get_contents($file)) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $lines += substr_count(is_array($token) ? $token[1] : $token, "\n");
        }
    }

    return $lines;
}

/** Sada vstupů, na které se ověřuje, že se chování nezměnilo. */
function cases(): array
{
    $out = [];

    foreach (['ppl', 'dhl', 'pickup'] as $carrier) {
        foreach ([[400, 129000], [3200, 89000], [12000, 320000]] as [$weight, $value]) {
            $out[] = [$carrier, $weight, $value];
        }
    }

    return $out;
}

echo "=== Přípravný refaktoring ===\n\n";

echo "    Zadání: přidat nový způsob dopravy — Balíkovnu.\n\n";

// --- 1. Cesta A: rovnou do stávajícího kódu -------------------------------

echo "1. Cesta A: přidat to rovnou\n\n";

$together = new Step1_Together\Shipping();

printf("    %s%s\n", pad('Balíkovna, 2 kg, 1 290 Kč', 30), czk($together->priceInCents('balikovna', 2000, 129000)));
printf("    %s%s\n\n", pad('Balíkovna, 12 kg, 890 Kč', 30), czk($together->priceInCents('balikovna', 12000, 89000)));

printf("    %s%d → %d  (+%d)\n\n",
    pad('řádků v Shipping.php', 30),
    codeLines(__DIR__ . '/Step0/Shipping.php'),
    codeLines(__DIR__ . '/Step1_Together/Shipping.php'),
    codeLines(__DIR__ . '/Step1_Together/Shipping.php') - codeLines(__DIR__ . '/Step0/Shipping.php'),
);

echo "    Funguje to a diff je malý. Jenže pravidlo o dopravě zdarma\n";
echo "    je teď v kódu dvakrát — a metoda je zase o kus delší.\n";
echo "    Příští dopravce bude o něco horší.\n\n";

// --- 2. Cesta B, krok 1: příprava -----------------------------------------

echo "2. Cesta B, krok 1: připravit místo (bez nové funkce)\n\n";

$original = new Step0\Shipping();
$prepared = Step2_Prepared\Shipping::default();

$same = 0;

foreach (cases() as [$carrier, $weight, $value]) {
    if ($original->priceInCents($carrier, $weight, $value) === $prepared->priceInCents($carrier, $weight, $value)) {
        ++$same;
    }
}

printf("    %s%d z %d\n", pad('shodné chování', 30), $same, count(cases()));
printf("    %s%s\n", pad('nová funkce přidána?', 30), 'ne — Balíkovna tu není');

try {
    $prepared->priceInCents('balikovna', 2000, 129000);
    echo "    Balíkovna funguje — CHYBA\n";
} catch (InvalidArgumentException $e) {
    printf("    %s%s\n\n", pad('zkouška Balíkovny', 30), $e->getMessage());
}

echo "    Tenhle krok nepřidal nic. Změnil jen strukturu — a testy\n";
echo "    to potvrzují. To je celý smysl: „make the change easy\n";
echo "    (warning: this may be hard)\".\n\n";

// --- 3. Cesta B, krok 2: funkce -------------------------------------------

echo "3. Cesta B, krok 2: přidat funkci\n\n";

$withFeature = new Step2_Prepared\Shipping(
    new Step2_Prepared\Ppl(),
    new Step2_Prepared\Dhl(),
    new Step2_Prepared\Pickup(),
    new Step3_Feature\Balikovna(),
);

printf("    %s%s\n", pad('Balíkovna, 2 kg, 1 290 Kč', 30), czk($withFeature->priceInCents('balikovna', 2000, 129000)));
printf("    %s%s\n\n", pad('Balíkovna, 12 kg, 890 Kč', 30), czk($withFeature->priceInCents('balikovna', 12000, 89000)));

printf("    %s%s\n", pad('nových souborů', 30), '1');
printf("    %s%s\n", pad('změn v existujících', 30), '0');
printf("    %s%d\n\n", pad('řádků nové funkce', 30), codeLines(__DIR__ . '/Step3_Feature/Balikovna.php'));

echo "    „…then make the easy change.\"\n\n";

// --- 4. Porovnání obou cest -----------------------------------------------

echo "4. Obě cesty vedle sebe\n\n";

$step0 = codeLines(__DIR__ . '/Step0/Shipping.php');
$step1 = codeLines(__DIR__ . '/Step1_Together/Shipping.php');
$step2 = codeLines(...glob(__DIR__ . '/Step2_Prepared/*.php'));
$step3 = codeLines(__DIR__ . '/Step3_Feature/Balikovna.php');

printf("    %s%s%s\n", pad('', 30), pad('cesta A', 24), 'cesta B');
printf("    %s%s%s\n", pad('commitů', 30), pad('1', 24), '2');
printf("    %s%s%s\n", pad('souborů', 30), pad('1', 24), '6');
printf("    %s%s%s\n", pad('řádků celkem po změně', 30), pad((string) $step1, 24), (string) ($step2 + $step3));
printf("    %s%s%s\n", pad('z toho samotná funkce', 30), pad('8 (uvnitř metody)', 24), $step3 . ' (vlastní soubor)');
printf("    %s%s%s\n", pad('pravidlo „zdarma od…" je', 30), pad('na 2 místech', 24), 'na 1 místě');
printf("    %s%s%s\n\n", pad('další dopravce si vyžádá', 30), pad('zásah do metody', 24), 'nový soubor');

echo "    Cesta B má trojnásobek řádků a dva commity. Vypadá dráž —\n";
echo "    a jednorázově je. Rozdíl je v tom, co stojí ten další\n";
echo "    dopravce: u A zase zásah do rostoucí metody, u B soubor.\n\n";

echo "    A jedno varování: kdyby žádný další dopravce nepřišel,\n";
echo "    byla cesta A správná. Přípravný refaktoring se dělá kvůli\n";
echo "    změně, kterou děláš teď — ne kvůli té, kterou tušíš.\n\n";

// --- 5. Proč dva commity --------------------------------------------------

echo "5. Proč to nedělat v jednom commitu\n\n";

printf("    %s%s%s\n", pad('commit', 30), pad('mění chování?', 18), 'co v něm recenzent hledá');
printf("    %s%s%s\n", pad('1. příprava (refaktoring)', 30), pad('ne', 18), 'jestli se opravdu nic nezměnilo');
printf("    %s%s%s\n\n", pad('2. Balíkovna (funkce)', 30), pad('ano', 18), 'jestli je nové pravidlo správně');

echo "    Fowler tomu říká dva klobouky: buď přidáváš funkci, nebo\n";
echo "    refaktoruješ — nikdy obojí zároveň. V jednom commitu by se\n";
echo "    ta jedna podstatná změna ztratila mezi přesuny.\n";
