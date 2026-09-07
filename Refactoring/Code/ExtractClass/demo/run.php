<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka refaktoringu Extract Class.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Lcom.php';
require __DIR__ . '/Before/Order.php';
require __DIR__ . '/After/DeliveryAddress.php';
require __DIR__ . '/After/Order.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function verdict(int $lcom): string
{
    return $lcom === 1
        ? 'drží pohromadě'
        : sprintf('%d nezávislé skupiny — kandidáti k prozkoumání', $lcom);
}

echo "=== Extract Class ===\n\n";

// --- 1. Která metoda sahá na která pole -----------------------------------

echo "1. Kdo na co sahá\n\n";

$before = Lcom::analyse(__DIR__ . '/Before/Order.php', 'Before\Order');

printf("    %s%s\n", pad('metoda', 24), 'pole, na která sahá');

foreach ($before['fieldsByMethod'] as $method => $fields) {
    printf(
        "    %s%s\n",
        pad($method . '()', 24),
        $fields === [] ? '—' : implode(', ', $fields),
    );
}

echo "\n";

// --- 2. LCOM4 --------------------------------------------------------------

echo "2. LCOM4: kolik nezávislých skupin ve třídě je\n\n";

printf("    %s%d   %s\n\n", pad('Before\\Order', 26), $before['value'], verdict($before['value']));

echo "    komponenty, které metrika našla:\n\n";

foreach ($before['components'] as $i => $component) {
    sort($component);
    printf("        %d. %s\n", $i + 1, implode(', ', array_map(static fn (string $m): string => $m . '()', $component)));
}

echo "\n    Tři skupiny metod, které se navzájem nedotýkají. Tohle není\n";
echo "    odhad — je to spočítané z toho, kdo sahá na co.\n\n";

// --- 3. Metrika říká, kudy řezat ------------------------------------------

echo "3. Ne každá komponenta je kandidát na vytažení\n\n";

$candidates = [
    'addItem, itemCount, totalInCents' => ['pojem' => 'položky objednávky', 'samostatně' => 'ne — bez objednávky nedávají smysl'],
    'confirm, status'                  => ['pojem' => 'stav objednávky',    'samostatně' => 'ne — je to vlastnost objednávky'],
    'setAddress, formattedAddress, isDomestic, postalCodeDigits' => ['pojem' => 'doručovací adresa', 'samostatně' => 'ANO — adresu má i dodavatel a pobočka'],
];

printf("    %s%s%s\n", pad('komponenta', 22), pad('pojem', 22), 'existuje samostatně?');

foreach ($candidates as $methods => $info) {
    printf(
        "    %s%s%s\n",
        pad(substr($methods, 0, 20), 22),
        pad($info['pojem'], 22),
        $info['samostatně'],
    );
}

echo "\n    Metrika našla tři komponenty, ale vytáhnout se má jedna.\n";
echo "    Položky a stav jsou obojí legitimní součást objednávky —\n";
echo "    že spolu technicky nesouvisejí, ještě neznamená, že mají\n";
echo "    bydlet jinde.\n\n";

echo "    Rozhodovací otázka není „je LCOM vysoké?\", ale:\n";
echo "    dá se ta skupina pojmenovat a existuje i bez téhle třídy?\n\n";

// --- 4. Po refaktoringu ----------------------------------------------------

echo "4. Po refaktoringu\n\n";

$afterOrder = Lcom::analyse(__DIR__ . '/After/Order.php', 'After\Order');
$afterAddress = Lcom::analyse(__DIR__ . '/After/DeliveryAddress.php', 'After\DeliveryAddress');

printf("    %s%s%s\n", pad('třída', 30), pad('LCOM4', 10), 'verdikt');
printf("    %s%s%s\n", pad('Before\\Order', 30), pad((string) $before['value'], 10), verdict($before['value']));
printf("    %s%s%s\n", pad('After\\Order', 30), pad((string) $afterOrder['value'], 10), verdict($afterOrder['value']));
printf("    %s%s%s\n\n", pad('After\\DeliveryAddress', 30), pad((string) $afterAddress['value'], 10), verdict($afterAddress['value']));

echo "    Pozor na první dva řádky: Order má LCOM4 pořád 3 a je to\n";
echo "    v pořádku. Zbyly v ní položky, stav a odkaz na adresu —\n";
echo "    tři skupiny, které k objednávce pojmově patří.\n\n";

echo "    LCOM4 = 1 není cíl. Je to ukazatel, který dává otázku,\n";
echo "    ne odpověď.\n\n";

// --- 5. Chování zůstalo stejné --------------------------------------------

echo "5. Chování se nezměnilo\n\n";

$old = new Before\Order('2026/001');
$old->addItem('MON-27', 799000, 1);
$old->addItem('KLA-01', 249000, 2);
$old->setAddress('Sokolovská 100', 'Praha', '186 00', 'CZ');

$new = new After\Order('2026/001');
$new->addItem('MON-27', 799000, 1);
$new->addItem('KLA-01', 249000, 2);
$new->deliverTo(new After\DeliveryAddress('Sokolovská 100', 'Praha', '186 00', 'CZ'));

printf("    %s%s%s\n", pad('', 22), pad('Before', 34), 'After');
printf("    %s%s%s\n", pad('součet', 22), pad((string) $old->totalInCents(), 34), (string) $new->totalInCents());
printf("    %s%s%s\n", pad('adresa', 22), pad($old->formattedAddress(), 34), $new->address()->format());
printf("    %s%s%s\n", pad('tuzemská', 22), pad($old->isDomestic() ? 'ano' : 'ne', 34), $new->address()->isDomestic() ? 'ano' : 'ne');
printf("    %s%s%s\n\n", pad('PSČ číslicemi', 22), pad($old->postalCodeDigits(), 34), $new->address()->postalCodeDigits());

// --- 6. Co se tím získalo --------------------------------------------------

echo "6. Co se tím získalo\n\n";

printf("    %s%s%s\n", pad('', 34), pad('Before', 16), 'After');
printf("    %s%s%s\n", pad('polí ve třídě Order', 34), pad('6', 16), '3');
printf("    %s%s%s\n", pad('metod ve třídě Order', 34), pad((string) count($before['fieldsByMethod']), 16), (string) count($afterOrder['fieldsByMethod']));
printf("    %s%s%s\n", pad('LCOM4 třídy Order', 34), pad((string) $before['value'], 16), (string) $afterOrder['value'] . '  (a je to OK)');
printf("    %s%s%s\n\n", pad('adresa jde použít jinde', 34), pad('ne', 16), 'ano');

echo "    Poslední řádek se snadno přehlédne: doručovací adresa je teď\n";
echo "    typ, který se dá použít i u dodavatele, u pobočky nebo\n";
echo "    ve fakturaci. Dokud byla součástí objednávky, nešlo to.\n";
