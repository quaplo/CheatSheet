<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Adapter.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Domain/ShippingQuote.php';
require __DIR__ . '/Domain/ShippingProvider.php';
require __DIR__ . '/Vendor/BalikovnaApi.php';
require __DIR__ . '/Vendor/GlobalShipClient.php';
require __DIR__ . '/Adapter/BalikovnaAdapter.php';
require __DIR__ . '/Adapter/GlobalShipAdapter.php';

use Adapter\BalikovnaAdapter;
use Adapter\GlobalShipAdapter;
use Domain\ShippingProvider;
use Vendor\BalikovnaApi;
use Vendor\GlobalShipClient;

function money(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}

/**
 * Naše aplikace. Zná JEN ShippingProvider.
 *
 * Vybere nejlevnějšího dopravce. O tom, že jeden počítá v kilogramech
 * a druhý v uncích, neví — a nikdy vědět nebude.
 *
 * @param list<ShippingProvider> $providers
 */
function cheapestQuote(array $providers, string $country, int $grams, int $orderValue): void
{
    $quotes = array_map(
        static fn (ShippingProvider $p) => $p->quote($country, $grams, $orderValue),
        $providers,
    );

    usort($quotes, static fn ($a, $b): int => $a->priceInCents <=> $b->priceInCents);

    foreach ($quotes as $i => $quote) {
        printf("        %s %s  %s  %d dní\n",
            $i === 0 ? '→' : ' ',
            mb_str_pad($quote->carrier, 12),
            mb_str_pad(money($quote->priceInCents), 12, ' ', STR_PAD_LEFT),
            $quote->deliveryDays,
        );
    }
}

echo "=== Adapter ===\n\n";

// --- 1. Co nabízejí cizí knihovny -----------------------------------------

echo "1. Dvě cizí knihovny, dva nekompatibilní světy\n\n";

$balikovna = new BalikovnaApi();
$globalShip = new GlobalShipClient();

$raw = $balikovna->spocitejCenu('CZ', 6.2);
printf("    BalikovnaApi::spocitejCenu('CZ', 6.2)\n");
printf("        → %s\n", json_encode($raw, JSON_UNESCAPED_UNICODE));

$rawGlobal = $globalShip->getRate(219, 'CZ');
printf("\n    GlobalShipClient::getRate(219, 'CZ')\n");
printf("        → RateResponse(amountUsdCents: %d, transitHours: %d, serviceName: '%s')\n",
    $rawGlobal->amountUsdCents, $rawGlobal->transitHours, $rawGlobal->serviceName);

echo "\n    Jiné metody, jiné jednotky, jiné návratové typy.\n";
echo "    Kilogramy proti uncím, koruny proti dolarům, text proti hodinám.\n";

// --- 2. Co z toho vidí aplikace -------------------------------------------

echo "\n2. Za adaptérem to vypadá takhle\n\n";

$providers = [
    new BalikovnaAdapter($balikovna),
    new GlobalShipAdapter($globalShip, usdToCzkRateInHundredths: 2350),
];

echo "    zásilka 6,2 kg do CZ, hodnota 3 200 Kč:\n";
cheapestQuote($providers, 'CZ', 6200, 320000);

echo "\n    Funkce cheapestQuote() nezná ani jednu z těch knihoven.\n";
echo "    Zná jen ShippingProvider — rozhraní, které si definovala\n";
echo "    naše aplikace podle toho, co potřebuje.\n";

// --- 3. Přidání třetího dopravce ------------------------------------------

echo "\n3. Co stojí přidat dalšího dopravce\n\n";
echo "        nová třída:            1 adaptér\n";
echo "        změny v doméně:        0\n";
echo "        změny v cheapestQuote: 0\n";
echo "        změny v cizí knihovně: 0 (a ani bychom nemohli)\n";

// --- 4. Kam patří rozhodnutí ----------------------------------------------

echo "\n4. Adaptér překládá — ale i překlad je rozhodnutí\n\n";
printf("    %s %s\n", mb_str_pad('cizí tvar', 22), 'rozhodnutí adaptéru');
printf("    %s %s\n", mb_str_pad('„2-3 dny“', 22), 'bereme HORNÍ odhad → 3');
printf("    %s %s\n", mb_str_pad('96 hodin', 22), 'zaokrouhlíme NAHORU → 4 dny');
printf("    %s %s\n", mb_str_pad('USD centy', 22), 'přepočet kurzem, který doména nezná');
printf("    %s %s\n", mb_str_pad('6200 g → unce', 22), 'ceil, ať neplatíme míň, než dopravce chce');

echo "\n    Tahle rozhodnutí patří do adaptéru, protože vyplývají\n";
echo "    z cizího systému. Kdyby prosákla do domény, začala by\n";
echo "    doména vědět, že někdo měří v uncích.\n";

// --- 5. Různé zásilky, různý vítěz ----------------------------------------

echo "\n5. Adaptéry jsou zaměnitelné, takže jdou porovnávat\n\n";

foreach ([
    ['popis' => 'lehká, levná', 'g' => 800, 'kc' => 89000],
    ['popis' => 'těžká, drahá', 'g' => 14000, 'kc' => 890000],
] as $case) {
    printf("    %s (%d g, %s):\n", $case['popis'], $case['g'], money($case['kc']));
    cheapestQuote($providers, 'CZ', $case['g'], $case['kc']);
    echo "\n";
}

echo "    Bez adaptérů by tohle srovnání nešlo napsat — dvě knihovny\n";
echo "    vracejí nesrovnatelné věci. Adaptér z nich udělá porovnatelné.\n";
