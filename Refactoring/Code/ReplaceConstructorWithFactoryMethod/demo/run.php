<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka refaktoringu Replace Constructor with Factory Method.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Before/Order.php';
require __DIR__ . '/Before/Usage.php';
require __DIR__ . '/After/Order.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

echo "=== Replace Constructor with Factory Method ===\n\n";

// --- 1. Co se dá z volání poznat -------------------------------------------

echo "1. Poznáš z volání, co se děje?\n\n";

$calls = [
    'new Order($n, $i, $customerId, null, false, false)' => 'objednávka z e-shopu',
    'new Order($n, $i, null, $partnerCode, true, true)'  => 'import od partnera',
    'new Order($n, $i, $cid, $pc, (bool) $row[…], false)' => 'načtení z databáze',
];

foreach ($calls as $call => $meaning) {
    printf("    %s\n", $call);
}

echo "\n    Tři volání téhož konstruktoru, tři úplně jiné situace.\n";
echo "    Rozdíl je v pořadí nullů a booleanů — a ten se při čtení\n";
echo "    nedá udržet v hlavě.\n\n";

printf("    %s\n", 'po refaktoringu:');
echo "        Order::placedByCustomer(\$n, \$i, \$customerId)\n";
echo "        Order::importedFromPartner(\$n, \$i, \$partnerCode)\n";
echo "        Order::reconstitute(\$row)\n\n";

// --- 2. Kolik kombinací konstruktor připouští ------------------------------

echo "2. Kolik stavů jde konstruktorem vytvořit\n\n";

$ctor = (new ReflectionClass(Before\Order::class))->getConstructor();
$boolParams = 0;
$nullableParams = 0;

foreach ($ctor->getParameters() as $param) {
    $type = $param->getType();

    if ($type instanceof ReflectionNamedType && $type->getName() === 'bool') {
        ++$boolParams;
    }

    if ($type !== null && $type->allowsNull()) {
        ++$nullableParams;
    }
}

$combinations = (2 ** $boolParams) * (2 ** $nullableParams);

printf("    %s%d\n", pad('parametrů konstruktoru', 32), $ctor->getNumberOfParameters());
printf("    %s%d\n", pad('z toho bool', 32), $boolParams);
printf("    %s%d\n", pad('z toho nullable', 32), $nullableParams);
printf("    %s%d\n", pad('kombinací jen z těchhle čtyř', 32), $combinations);
printf("    %s%d\n\n", pad('smysluplných situací', 32), 3);

printf("    %d kombinací, ze kterých dávají smysl 3.\n", $combinations);
echo "    Zbylých 13 jde vytvořit a nic je nezastaví — třeba\n";
echo "    objednávku, která má zákazníka i partnera zároveň.\n\n";

// --- 3. Nesmyslný stav v praxi --------------------------------------------

echo "3. Nesmyslná objednávka, kterou nic nezastaví\n\n";

$nonsense = new Before\Order('2026/666', ['MON-27'], 'alice', 'PARTNER-X', true, true);

printf("    %s%s\n", pad('zákazník', 20), $nonsense->customerId ?? '—');
printf("    %s%s\n", pad('partner', 20), $nonsense->partnerCode ?? '—');
printf("    %s%s\n\n", pad('vytvořeno?', 20), 'ano — a nikdo se neptal');

echo "    Objednávka od zákazníka i od partnera současně. Takový\n";
echo "    stav v doméně neexistuje, ale konstruktor ho vyrobí.\n\n";

$empty = new Before\Order('2026/667', [], 'alice', null, false, false);
printf("    prázdná objednávka:   %d položek — taky prošla\n\n", count($empty->items));

// --- 4. Po refaktoringu ----------------------------------------------------

echo "4. Po refaktoringu: jen tři cesty\n\n";

$eshop = After\Order::placedByCustomer('2026/001', ['MON-27'], 'alice');
$import = After\Order::importedFromPartner('2026/002', ['KLA-01'], 'PARTNER-X');

printf("    %s%s%s%s\n", pad('cesta', 26), pad('zákazník', 12), pad('partner', 12), 'zaplaceno');
printf("    %s%s%s%s\n", pad('placedByCustomer', 26), pad($eshop->customerId ?? '—', 12), pad($eshop->partnerCode ?? '—', 12), $eshop->isPaid ? 'ano' : 'ne');
printf("    %s%s%s%s\n\n", pad('importedFromPartner', 26), pad($import->customerId ?? '—', 12), pad($import->partnerCode ?? '—', 12), $import->isPaid ? 'ano' : 'ne');

echo "    Kombinace zákazník + partner se nedá vytvořit. Ne proto,\n";
echo "    že by ji někdo kontroloval, ale protože k ní nevede cesta.\n\n";

try {
    After\Order::placedByCustomer('2026/003', [], 'alice');
    echo "    prázdná objednávka prošla — CHYBA\n\n";
} catch (DomainException $e) {
    printf("    prázdná objednávka:   %s\n\n", $e->getMessage());
}

// --- 5. Konstruktor je soukromý -------------------------------------------

echo "5. Obejít to nejde\n\n";

$beforeCtor = (new ReflectionClass(Before\Order::class))->getConstructor();
$afterCtor = (new ReflectionClass(After\Order::class))->getConstructor();

printf("    %s%s%s\n", pad('', 26), pad('Before', 16), 'After');
printf("    %s%s%s\n", pad('konstruktor', 26), pad($beforeCtor->isPublic() ? 'public' : 'private', 16), $afterCtor->isPublic() ? 'public' : 'private');

$factories = array_filter(
    (new ReflectionClass(After\Order::class))->getMethods(ReflectionMethod::IS_PUBLIC),
    static fn (ReflectionMethod $m): bool => $m->isStatic(),
);

printf("    %s%s%d\n\n", pad('pojmenovaných cest', 26), pad('0', 16), count($factories));

foreach ($factories as $factory) {
    $n = $factory->getNumberOfParameters();
    $word = match (true) {
        $n === 1 => 'parametr',
        $n < 5 => 'parametry',
        default => 'parametrů',
    };

    printf("        %s(%d %s)\n", $factory->getName(), $n, $word);
}

echo "\n";

// --- 6. Proč to v PHP nejde jinak -----------------------------------------

echo "6. V PHP to jinak ani nejde\n\n";

printf("    %s%s\n", pad('Java, C#', 24), 'přetížení konstruktoru — víc konstruktorů');
printf("    %s%s\n", pad('PHP', 24), 'jeden konstruktor, tečka');
printf("    %s%s\n\n", pad('důsledek pro PHP', 24), 'statické továrny jsou JEDINÁ cesta');

echo "    V jazycích s přetěžováním je tenhle refaktoring o čitelnosti\n";
echo "    a o možnosti vrátit podtyp. V PHP je navíc jediný způsob,\n";
echo "    jak mít víc pojmenovaných způsobů vytvoření.\n";
