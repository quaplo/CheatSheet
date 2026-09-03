<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Builder.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Domain/OrderItem.php';
require __DIR__ . '/Domain/Order.php';
require __DIR__ . '/Domain/OrderBuilder.php';
require __DIR__ . '/Test/OrderMother.php';

use Domain\Order;
use Domain\OrderBuilder;
use Domain\OrderItem;
use Test\OrderMother;

$now = new DateTimeImmutable('2026-09-01 10:00:00');

echo "=== Builder ===\n\n";

// --- 1. Konstruktor, který nikdo nepřečte ---------------------------------

echo "1. Přímé volání konstruktoru\n\n";
echo "        new Order(\n";
echo "            'OBJ-001', 'a@b.cz', \$items, 'kurýr', 'převod',\n";
echo "            null, 'PODZIM26', false, \$now,\n";
echo "        );\n";

$direct = new Order('OBJ-001', 'a@b.cz', [new OrderItem('MON-27', 799000, 1)], 'kurýr', 'převod', null, 'PODZIM26', false, $now);

printf("\n    %s\n", $direct->describe());

$reflection = new ReflectionClass(Order::class);
printf("\n    parametrů konstruktoru: %d\n", $reflection->getConstructor()?->getNumberOfParameters() ?? 0);
echo "    Co znamená ten `null` a ten `false`? Bez otevření třídy nic.\n";

// --- 2. Totéž builderem ----------------------------------------------------

echo "\n2. Totéž přes builder\n\n";
echo "        OrderBuilder::for('OBJ-002', 'a@b.cz', \$now)\n";
echo "            ->withItem('MON-27', 799000)\n";
echo "            ->shippedBy('kurýr')\n";
echo "            ->paidBy('převod')\n";
echo "            ->withCoupon('PODZIM26')\n";
echo "            ->build();\n";

$built = OrderBuilder::for('OBJ-002', 'a@b.cz', $now)
    ->withItem('MON-27', 799000)
    ->shippedBy('kurýr')
    ->paidBy('převod')
    ->withCoupon('PODZIM26')
    ->build();

printf("\n    %s\n", $built->describe());
echo "\n    Každá část má jméno. O tom, co se nenastavilo, se mlčí —\n";
echo "    a výchozí hodnoty jsou v builderu, ne v devíti `null`.\n";

// --- 3. Sestavování po částech --------------------------------------------

echo "\n3. Části se přidávají postupně\n";

$builder = OrderBuilder::for('OBJ-003', 'zakaznik@example.com', $now);

echo "\n        zákazník přidá monitor…\n";
$builder->withItem('MON-27', 799000);

echo "        …pak klávesnici…\n";
$builder->withItem('KLA-01', 129000, 2);

echo "        …a rozmyslí si dopravu\n";
$builder->shippedBy('osobní odběr');

$cart = $builder->build();
printf("\n    %s\n", $cart->describe());

echo "\n    Tohle je rozdíl proti továrně: ta vyrobí objekt jedním voláním.\n";
echo "    Builder drží rozdělanou práci, dokud někdo neřekne build().\n";

// --- 4. Pravidla zůstávají v objektu --------------------------------------

echo "\n4. Builder NENÍ místo pro doménová pravidla\n";

echo "\n    prázdná objednávka:\n";

try {
    OrderBuilder::for('OBJ-004', 'a@b.cz', $now)->build();
} catch (DomainException $e) {
    printf("        %s\n", $e->getMessage());
}

echo "\n    dárek bez vzkazu (obejití builderu, přímý konstruktor):\n";

try {
    new Order('OBJ-005', 'a@b.cz', [new OrderItem('X', 100, 1)], 'balíkovna', 'karta', null, null, true, $now);
} catch (DomainException $e) {
    printf("        %s\n", $e->getMessage());
}

echo "\n    Obě výjimky přišly z konstruktoru Order, ne z builderu.\n";
echo "    Kdyby pravidla byla v builderu, obešel by je každý, kdo si\n";
echo "    objednávku sestaví jinak.\n";

// --- 5. Test data builder --------------------------------------------------

echo "\n5. Nejužitečnější použití v PHP: testovací data\n\n";
echo "        OrderMother::any()->withCoupon('SLEVA10')->build()\n";
echo "        OrderMother::gift()\n";

$withCoupon = OrderMother::withCoupon('SLEVA10');
$gift = OrderMother::gift();

printf("\n    %s\n", $withCoupon->describe());
printf("    %s\n", $gift->describe());

echo "\n    Test řekne jen to, co je pro NĚJ podstatné, a o zbytku mlčí.\n";
echo "    Kdyby do konstruktoru přibyl desátý parametr, změní se jedno\n";
echo "    místo — ne sto testů.\n";

// --- 6. Kdy builder nepotřebuješ ------------------------------------------

echo "\n6. Kdy to nepotřebuješ\n\n";
echo "        // Pojmenované argumenty pokryjí většinu případů:\n";
echo "        new Order(\n";
echo "            number: 'OBJ-006',\n";
echo "            customerEmail: 'a@b.cz',\n";
echo "            isGift: false,\n";
echo "            couponCode: 'PODZIM26',\n";
echo "            // …\n";
echo "        );\n";

echo "\n    Od PHP 8 mají pojmenované argumenty většinu výhod builderu:\n";
echo "    každá hodnota má jméno a nepovinné se dají vynechat.\n";
echo "\n    Builder má navrch jen tam, kde se objekt sestavuje POSTUPNĚ\n";
echo "    (jako v sekci 3), nebo kde chceš pojmenovat celé kombinace\n";
echo "    (asGift() nastaví dvě věci najednou).\n";
