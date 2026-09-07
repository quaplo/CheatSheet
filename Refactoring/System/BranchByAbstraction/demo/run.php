<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka techniky Branch by Abstraction.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/ShippingQuote.php';
require __DIR__ . '/Shipment.php';
require __DIR__ . '/ShippingCalculator.php';
require __DIR__ . '/LegacyTableCalculator.php';
require __DIR__ . '/WeightBasedCalculator.php';
require __DIR__ . '/SwitchingCalculator.php';
require __DIR__ . '/CheckoutService.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/** Zdrojový kód bez komentářů — jinak by se do měření počítaly i zmínky v dokumentaci. */
function codeWithoutComments(string $file): string
{
    $out = '';

    foreach (token_get_all(file_get_contents($file)) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        $out .= is_array($token) ? $token[1] : $token;
    }

    return $out;
}

/** Pět zásilek na ukázku rozdílů. @return list<Shipment> */
function sampleShipments(): array
{
    return [
        new Shipment('CZ', 400, 129000),
        new Shipment('CZ', 3200, 89000),
        new Shipment('SK', 1500, 199000),
        new Shipment('DE', 800, 149000),
        new Shipment('CZ', 2100, 320000),
    ];
}

/** Větší vzorek, aby byly podíly provozu čitelné. @return list<Shipment> */
function trafficSample(int $count = 200): array
{
    $countries = ['CZ', 'CZ', 'CZ', 'SK', 'DE'];
    $shipments = [];

    for ($i = 0; $i < $count; ++$i) {
        $shipments[] = new Shipment(
            $countries[$i % count($countries)],
            200 + $i * 37 % 5000,
            50000 + $i * 1700 % 400000,
        );
    }

    return $shipments;
}

echo "=== Branch by Abstraction ===\n\n";

$old = new LegacyTableCalculator();
$new = new WeightBasedCalculator();

// --- 1. Volající nezná implementaci ---------------------------------------

echo "1. Po kroku 2: volající zná jen abstrakci\n\n";

$checkout = new CheckoutService($old);

printf("    %s%s\n", pad('CheckoutService dostal:', 30), 'ShippingCalculator (rozhraní)');
$checkoutCode = codeWithoutComments(__DIR__ . '/CheckoutService.php');

printf("    %s%s\n", pad('zná LegacyTableCalculator?', 30), str_contains($checkoutCode, 'LegacyTable') ? 'ano' : 'ne');
printf("    %s%s\n\n", pad('zná WeightBasedCalculator?', 30), str_contains($checkoutCode, 'WeightBased') ? 'ano' : 'ne');

echo '    ' . $checkout->summarise(new Shipment('CZ', 400, 129000)) . "\n\n";

echo "    Tenhle krok je sám o sobě zlepšením. I kdyby se dál\n";
echo "    nepokračovalo, volající je odstíněný od implementace.\n\n";

// --- 2. Obě implementace vedle sebe ---------------------------------------

echo "2. Po kroku 3: obě verze existují, provoz jede po staré\n\n";

printf("    %s%s%s\n", pad('zásilka', 26), pad('stará', 26), 'nová');

foreach (sampleShipments() as $shipment) {
    printf(
        "    %s%s%s\n",
        pad(sprintf('%s %d g / %d Kč', $shipment->countryCode, $shipment->weightInGrams, intdiv($shipment->orderValueInCents, 100)), 26),
        pad($old->quoteFor($shipment)->format(), 26),
        $new->quoteFor($shipment)->format(),
    );
}

echo "\n    Nová implementace je hotová a otestovaná, ale nikdo ji zatím\n";
echo "    nepoužívá. Produkce jede beze změny.\n\n";

// --- 3. Porovnávací režim --------------------------------------------------

echo "3. Porovnávací režim — kde se to rozchází\n\n";

$switching = new SwitchingCalculator($old, $new, newSharePercent: 0, compare: true);

foreach (sampleShipments() as $shipment) {
    $switching->quoteFor($shipment);
}

printf("    zásilek:               %d\n", count(sampleShipments()));
printf("    rozdílů:               %d\n\n", count($switching->mismatches));

foreach ($switching->mismatches as $mismatch) {
    echo '        ' . $mismatch . "\n";
}

echo "\n    Obě verze běží, ale odpovídá pořád stará. Rozdíly se jen\n";
echo "    zaznamenávají — a je z nich vidět, že nová verze mění ceny.\n";
echo "    To je rozhodnutí pro byznys, ne pro vývojáře.\n\n";

// --- 4. Postupné přepínání ------------------------------------------------

echo "4. Po kroku 4: přepínání po částech provozu\n\n";

$switching = new SwitchingCalculator($old, $new, newSharePercent: 0);

$traffic = trafficSample();

printf("    %s%s%s\n", pad('nastaveno', 16), pad('obslouženo novou', 22), 'skutečný podíl');

foreach ([0, 10, 25, 50, 100] as $percent) {
    $switching->setNewShare($percent);
    $switching->servedByNew = 0;

    foreach ($traffic as $shipment) {
        $switching->quoteFor($shipment);
    }

    printf(
        "    %s%s%.1f %%\n",
        pad($percent . ' %', 16),
        pad($switching->servedByNew . ' z ' . count($traffic), 22),
        $switching->servedByNew / count($traffic) * 100,
    );
}

echo "\n    Návrat zpět je změna jednoho čísla na nulu. Ne nasazení,\n";
echo "    ne revert, ne hotfix — jen přepnutí.\n\n";

// --- 5. Táž zásilka dostane touž odpověď ----------------------------------

echo "5. Rozdělení provozu musí být deterministické\n\n";

$switching->setNewShare(50);
$shipment = new Shipment('SK', 1500, 199000);

$answers = [];

for ($i = 0; $i < 5; ++$i) {
    $answers[] = $switching->quoteFor($shipment)->format();
}

printf("    pět dotazů na touž zásilku:\n");

foreach ($answers as $i => $answer) {
    printf("        %d. %s\n", $i + 1, $answer);
}

printf("\n    všechny odpovědi stejné:  %s\n\n", count(array_unique($answers)) === 1 ? 'ano' : 'NE — chyba');

echo "    Kdyby se rozhodovalo náhodně, zákazník by při obnovení\n";
echo "    stránky viděl jinou cenu dopravy. Proto se rozděluje\n";
echo "    podle otisku vstupu, ne podle náhody.\n\n";

// --- 6. Kde se dá zastavit -------------------------------------------------

echo "6. Kde se dá zastavit\n\n";

$steps = [
    '1. abstrakce existuje'          => 'ano — kód je čitelnější',
    '2. volající přepnuti na ni'     => 'ano — implementace jde vyměnit',
    '3. nová implementace hotová'    => 'ano — leží nepoužitá, nevadí',
    '4. přepnuto na novou'           => 'ano — stará je záloha',
    '5. stará smazána'               => 'hotovo',
];

printf("    %s%s\n", pad('po kroku', 34), 'dá se tu zůstat?');

foreach ($steps as $step => $canStop) {
    printf("    %s%s\n", pad($step, 34), $canStop);
}

echo "\n    Tohle je hlavní rozdíl proti přepisu ve větvi: každý krok\n";
echo "    je sám o sobě bezpečný a projekt v něm může zůstat.\n";
