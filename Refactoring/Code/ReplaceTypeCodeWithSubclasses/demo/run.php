<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka refaktoringu Replace Type Code with Subclasses
 * a jeho druhé podoby, které se dřív říkalo State/Strategy.
 *
 * Spuštění:  php run.php
 */

foreach (['TypeCode', 'Subclasses', 'Delegated'] as $dir) {
    foreach (glob(__DIR__ . '/' . $dir . '/*.php') as $file) {
        require_once $file;
    }
}

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

echo "=== Replace Type Code with Subclasses ===\n";
echo "    (dřív také Replace Type Code with State/Strategy)\n\n";

echo "    Druh zákazníka jako řetězec:\n\n";
echo "        \$this->tier === 'premium' ? 10 : 0\n\n";
echo "    Velký switch nikde není. Smell je ta hodnota sama —\n";
echo "    nese chování, ale je to jen text.\n\n";

// --- 1. Obě náhrady dávají totéž -------------------------------------------

echo "1. Obě náhrady se chovají stejně — dokud se nic nemění\n\n";

$typeCode = new TypeCode\Customer(1, 'Nováková', 'standard');
$subclass = new Subclasses\StandardCustomer(1, 'Nováková');
$delegated = new Delegated\Customer(1, 'Nováková', new Delegated\StandardTier());

printf("    %s%s%s%s\n", pad('', 26), pad('type code', 14), pad('podtřídy', 12), 'delegace');
printf(
    "    %s%s%s%d\n",
    pad('discountPercent()', 26),
    pad((string) $typeCode->discountPercent(), 14),
    pad((string) $subclass->discountPercent(), 12),
    $delegated->discountPercent(),
);
printf(
    "    %s%s%s%d\n\n",
    pad('freeShippingFromInCents()', 26),
    pad((string) $typeCode->freeShippingFromInCents(), 14),
    pad((string) $subclass->freeShippingFromInCents(), 12),
    $delegated->freeShippingFromInCents(),
);

// --- 2. Povýšení -----------------------------------------------------------

echo "2. A teď ta otázka, která o všem rozhoduje: mění se ten druh?\n\n";

echo "    Zákaznice nasbírala dost nákupů a stává se prémiovou.\n\n";

$subclass->addPurchase(250000);
$delegated->addPurchase(250000);

// Košík si zákazníka drží — úplně běžná situace.
$subclassInCart = $subclass;
$delegatedInCart = $delegated;

$subclassIdBefore = spl_object_id($subclass);
$delegatedIdBefore = spl_object_id($delegated);

$subclass = Subclasses\Upgrade::toPremium($subclass);
$delegated->upgradeTo(new Delegated\PremiumTier());

printf("    %s%s%s\n", pad('', 34), pad('podtřídy', 16), 'delegace');
printf(
    "    %s%s%s\n",
    pad('jak se povýší', 34),
    pad('nový objekt', 16),
    'výměna pole',
);
printf(
    "    %s%s%s\n",
    pad('je to týž objekt?', 34),
    pad(spl_object_id($subclass) === $subclassIdBefore ? 'ano' : 'NE', 16),
    spl_object_id($delegated) === $delegatedIdBefore ? 'ano' : 'NE',
);
printf(
    "    %s%s%s\n",
    pad('sleva po povýšení', 34),
    pad($subclass->discountPercent() . ' %', 16),
    $delegated->discountPercent() . ' %',
);
printf(
    "    %s%s%s\n\n",
    pad('sleva u reference v košíku', 34),
    pad($subclassInCart->discountPercent() . ' %', 16),
    $delegatedInCart->discountPercent() . ' %',
);

if ($subclassInCart->discountPercent() !== $subclass->discountPercent()) {
    echo "    Tady je ta past. Košík si drží STARÝ objekt, protože\n";
    echo "    povýšení vyrobilo nový. Zákaznice je prémiová a v košíku\n";
    printf("    má pořád slevu %d %%.\n\n", $subclassInCart->discountPercent());
}

// --- 3. Co se muselo přenést ----------------------------------------------

echo "3. A co se při tom stěhování snadno ztratí\n\n";

$properties = array_map(
    static fn (ReflectionProperty $p): string => $p->getName(),
    (new ReflectionClass(Subclasses\Customer::class))->getProperties(),
);

$upgradeSource = file_get_contents(__DIR__ . '/Subclasses/Upgrade.php');
$transferred = array_filter(
    $properties,
    static fn (string $name): bool => str_contains($upgradeSource, $name),
);

printf("    %s%d\n", pad('polí, která nese zákazník', 40), count($properties));
printf("    %s%d\n", pad('přenesených ručně v Upgrade', 40), count($transferred));
printf(
    "    %s%d %s\n\n",
    pad('lifetimeValue po povýšení', 40),
    $subclass->lifetimeValueInCents(),
    $subclass->lifetimeValueInCents() === 250000 ? '— přeneseno správně' : '— ZTRACENO',
);

echo "    Tady to vyšlo, protože pole jsou tři a přenášejí se ručně\n";
echo "    všechna. U entity s dvaceti poli je tenhle kopírovací\n";
echo "    konstruktor místo, kam se chodí zapomínat — a nic ti\n";
echo "    neřekne, že jsi na jedno zapomněl.\n\n";

// --- 4. Rozhodnutí ---------------------------------------------------------

echo "4. Podle čeho se rozhodnout\n\n";

printf("    %s%s%s\n", pad('', 42), pad('podtřídy', 14), 'delegace');
printf("    %s%s%s\n", pad('druh se za života nemění', 42), pad('ano', 14), 'jde taky');
printf("    %s%s%s\n", pad('druh se mění', 42), pad('NE', 14), 'ano');
printf("    %s%s%s\n", pad('objekt má identitu, kterou drží ostatní', 42), pad('NE', 14), 'ano');
printf("    %s%s%s\n", pad('varianty se liší jen chováním', 42), pad('ano', 14), 'ano');
printf("    %s%s%s\n\n", pad('objekt už dědí z něčeho jiného', 42), pad('NE', 14), 'ano');

echo "    Tři z pěti řádků říkají „podtřídy ne\". Proto to Fowler\n";
echo "    v prvním vydání vedl jako dvě různé techniky — a proto se\n";
echo "    ta druhá jmenovala State/Strategy.\n";
