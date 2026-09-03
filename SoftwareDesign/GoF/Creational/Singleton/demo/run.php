<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Singleton.
 *
 * Spuštění:  php run.php
 *
 * Tenhle pattern je v katalogu hlavně proto, aby bylo vidět,
 * co za něj platíš — ne aby se používal.
 */

require __DIR__ . '/Classic/PriceConfig.php';
require __DIR__ . '/Classic/ShippingCalculator.php';
require __DIR__ . '/Better/PriceConfig.php';
require __DIR__ . '/Better/ShippingCalculator.php';

function money(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

echo "=== Singleton ===\n\n";

// --- 1. Funguje ------------------------------------------------------------

echo "1. Dělá, co slíbil\n";

$a = Classic\PriceConfig::getInstance();
$b = Classic\PriceConfig::getInstance();

printf("\n    \$a === \$b:  %s\n", $a === $b ? 'true' : 'false');
printf("    DPH:        %d %%\n", $a->vatPercent);

echo "\n    Jedna instance, dostupná odkudkoli. To je celý slib.\n";

// --- 2. Skrytá závislost --------------------------------------------------

echo "\n2. Závislost, kterou z třídy nepoznáš\n";

$classic = new Classic\ShippingCalculator();

printf("\n    new ShippingCalculator()   ← konstruktor je PRÁZDNÝ\n");
printf("    doprava pro 890 Kč:  %s\n", money($classic->calculate(89000)));

$reflection = new ReflectionClass(Classic\ShippingCalculator::class);
printf("\n    parametrů konstruktoru: %d\n", $reflection->getConstructor()?->getNumberOfParameters() ?? 0);
printf("    skutečných závislostí:  1  (PriceConfig, schovaná uvnitř metody)\n");

echo "\n    Tohle je hlavní problém singletonu. Ne to, že je jen jedna\n";
echo "    instance — ale že to na třídě není vidět. Kdo ji chce použít,\n";
echo "    musí přečíst tělo metody.\n";

// --- 3. Test, který nejde napsat ------------------------------------------

echo "\n3. Test s jinou konfigurací\n";

echo "\n    Chci ověřit, že při hranici 500 Kč je doprava zdarma od 500 Kč.\n";
echo "    Se singletonem:\n";

$classicResult = $classic->calculate(60000);
printf("        doprava pro 600 Kč: %s   ← pořád platí globální hranice 1 500 Kč\n", money($classicResult));
echo "        jinou konfiguraci nepodstrčím — instance je jedna a globální\n";

echo "\n    Bez singletonu:\n";

$cheap = new Better\ShippingCalculator(new Better\PriceConfig(freeShippingFromCents: 50000));
printf("        doprava pro 600 Kč: %s   ← test si nastavil vlastní hranici\n", money($cheap->calculate(60000)));

// --- 4. Dvě konfigurace vedle sebe ----------------------------------------

echo "\n4. Co když je potřeba víc než jedna?\n";

$cz = new Better\ShippingCalculator(new Better\PriceConfig(vatPercent: 21, freeShippingFromCents: 150000));
$sk = new Better\ShippingCalculator(new Better\PriceConfig(vatPercent: 20, freeShippingFromCents: 200000));

printf("\n    objednávka za 1 700 Kč:\n");
printf("        CZ (zdarma od 1 500 Kč):  %s\n", money($cz->calculate(170000)));
printf("        SK (zdarma od 2 000 Kč):  %s\n", money($sk->calculate(170000)));

echo "\n    Singleton tvrdí, že instance smí být jen jedna. Jenže\n";
echo "    „jen jedna\" je požadavek, který se mění — a při expanzi\n";
echo "    do druhé země se z něj stane přepisování celé aplikace.\n";

// --- 5. Sdílený stav mezi testy -------------------------------------------

echo "\n5. Stav, který přežije, co neměl\n";

$config = Classic\PriceConfig::getInstance();
$config->vatPercent = 0;                      // „jen v tomhle testu“

printf("\n    test A nastaví DPH na 0 %%\n");
printf("    test B pak vidí:  %d %%   ← a neví proč\n", Classic\PriceConfig::getInstance()->vatPercent);

echo "\n    Testy přestanou být nezávislé a začnou selhávat podle pořadí,\n";
echo "    ve kterém běží. Hledá se to velmi špatně.\n";

// --- 6. Shrnutí ------------------------------------------------------------

echo "\n6. Co za to platíš\n\n";
printf("    %s %s %s\n", mb_str_pad('', 30), mb_str_pad('Singleton', 14), 'DI kontejner');
printf("    %s %s %s\n", mb_str_pad('jedna instance v aplikaci', 30), mb_str_pad('ano', 14), 'ano');
printf("    %s %s %s\n", mb_str_pad('závislost je vidět', 30), mb_str_pad('NE', 14), 'ano');
printf("    %s %s %s\n", mb_str_pad('jde podstrčit v testu', 30), mb_str_pad('NE', 14), 'ano');
printf("    %s %s %s\n", mb_str_pad('dvě konfigurace vedle sebe', 30), mb_str_pad('NE', 14), 'ano');
printf("    %s %s %s\n", mb_str_pad('testy jsou nezávislé', 30), mb_str_pad('NE', 14), 'ano');
printf("    %s %s %s\n", mb_str_pad('práce navíc', 30), mb_str_pad('žádná', 14), 'řádek v konfiguraci');

echo "\n    Ten poslední řádek je celý důvod, proč singleton vznikl —\n";
echo "    a jediný, který dnes neplatí. DI kontejner udělá „jedna\n";
echo "    instance\" zadarmo a nic z toho ostatního nezkazí.\n";
