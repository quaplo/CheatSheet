<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Active Record.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/ActiveRecord.php';
require __DIR__ . '/Models.php';
require __DIR__ . '/schema.php';
require __DIR__ . '/DomainStyle.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/** Počet řádků skutečného kódu — bez komentářů a prázdných řádků. */
function codeLines(string $file): int
{
    $lines = 0;

    foreach (token_get_all(file_get_contents($file)) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        $lines += substr_count(is_array($token) ? $token[1] : $token, "\n");
    }

    return $lines;
}

echo "=== Active Record ===\n\n";

$connection = createDatabase();
ActiveRecord::useConnection($connection);

// --- 1. Co pattern skutečně šetří ------------------------------------------

echo "1. Proč tenhle pattern existuje\n\n";

ActiveRecord::resetQueryCount();

$order = Order::find('2024/001');
$order->status = 'potvrzená';
$order->save();

echo '    $order = Order::find(\'2024/001\');' . "\n";
echo '    $order->status = \'potvrzená\';' . "\n";
echo '    $order->save();' . "\n\n";

printf("    výsledek:              %s\n", Order::find('2024/001')->format());
printf("    tříd, které to řeší:   1   (samotný model)\n");
printf("    řádků modelu Order:    %d\n\n", codeLines(__DIR__ . '/Models.php'));

echo "    Žádné repository, žádný mapper, žádná konfigurace.\n";
echo "    Pro CRUD nad tabulkou je tohle správná odpověď —\n";
echo "    vrstva navíc by nepřinesla nic než práci.\n\n";

// --- 2. Kde je hranice: N+1 ------------------------------------------------

echo "2. Vazby se načítají potichu — N+1\n\n";

ActiveRecord::resetQueryCount();

$orders = Order::all();
$lines = [];

foreach ($orders as $each) {
    // Vypadá to jako práce s objektem. Je to dotaz do databáze.
    $lines[] = sprintf('%s → %s', $each->number, $each->customer()->name);
}

$naive = ActiveRecord::$queryCount;

foreach ($lines as $line) {
    echo '        ' . $line . "\n";
}

printf("\n    dotazů:                %d   (1 na objednávky + %d na zákazníky)\n\n", $naive, $naive - 1);

// Ruční eager loading — přesně to, co dělá Eloquent přes with().
ActiveRecord::resetQueryCount();

$orders = Order::all();
$customers = [];

foreach (Customer::all() as $customer) {
    $customers[$customer->id] = $customer;
}

foreach ($orders as $each) {
    $customers[$each->customer_id]->name;
}

printf("    s eager loadingem:     %d   ← Eloquent to řeší přes with()\n\n", ActiveRecord::$queryCount);

echo "    Ta chyba není v patternu, ale v tom, jak snadno se dá\n";
echo "    udělat: `\$order->customer()` vypadá jako čtení vlastnosti.\n";
echo "    Data Mapper má tentýž problém, jen ho hůř schová.\n\n";

// --- 3. Doménové pravidlo potřebuje databázi -------------------------------

echo "3. Pravidlo, které se bez databáze neotestuje\n\n";

// a) Active Record — objekt jde vyrobit jen přes databázi.
$fromDatabase = Order::find('2024/003');

echo '    ' . pad('', 30) . pad('Active Record', 18) . "doménová entita\n";
echo '    ' . pad('lze vytvořit bez DB', 30) . pad('ne', 18) . "ano\n";
echo '    ' . pad('test pravidla chce schéma', 30) . pad('ano', 18) . "ne\n";

// b) Doménová entita — stačí konstruktor.
$plain = new DomainStyle\Order('2024/003', 'alice', 249000, 'odeslaná');

echo '    ' . pad('stejné pravidlo vrací', 30)
    . pad($fromDatabase->canBeCancelled() ? 'true' : 'false', 18)
    . ($plain->canBeCancelled() ? 'true' : 'false') . "   ← shodně, o pravidlo tu nejde\n\n";

try {
    $fromDatabase->cancel();
} catch (DomainException $e) {
    echo '    ' . $e->getMessage() . "\n\n";
}

echo "    Pravidlo je v obou případech totéž. Rozdíl je v tom, co\n";
echo "    musíš postavit, abys ho spustil: u Active Recordu schéma,\n";
echo "    spojení a data. U domény konstruktor.\n\n";

// --- 4. Statické spojení je globální stav ----------------------------------

echo "4. Spojení je statické — objekt si ho bere sám\n\n";

$saved = (new ReflectionClass(ActiveRecord::class))->getProperty('connection');
$backup = $saved->getValue();
$saved->setValue(null, null);

try {
    Order::find('2024/001');
} catch (RuntimeException $e) {
    echo '    ' . $e->getMessage() . "\n\n";
}

$saved->setValue(null, $backup);

echo "    `Order::find()` funguje odkudkoli a nic si nežádá —\n";
echo "    to je celé pohodlí patternu. Zároveň to znamená, že\n";
echo "    závislost na databázi není v žádném konstruktoru vidět\n";
echo "    a v testu ji nejde vyměnit jinak než globálně.\n\n";

// --- 5. Změna schématu se propíše do kódu ----------------------------------

echo "5. Sloupec se přejmenuje — kolik míst se změní\n\n";

// Jen názvy, které existují POUZE v databázi — ne slova jako „status“,
// která má doména stejně a nic o schématu neprozrazují.
$columns = ['total_cents', 'customer_id'];

// Počítá jen skutečné použití (`->sloupec`), ne tenhle měřicí kód.
$countIn = static function (string $file) use ($columns): int {
    return preg_match_all(
        '/->(' . implode('|', $columns) . ')\b/',
        file_get_contents($file),
    );
};

echo '    ' . pad('', 34) . "výskytů názvů sloupců\n";
echo '    ' . pad('Active Record model', 34) . $countIn(__DIR__ . '/Models.php') . "\n";
echo '    ' . pad('volající kód (tohle demo)', 34) . $countIn(__DIR__ . '/run.php') . "   ← a tady je ten problém\n";
echo '    ' . pad('doménová entita', 34) . $countIn(__DIR__ . '/DomainStyle.php') . "   ← jména sloupců zná jen mapper\n\n";

echo "    K tomu ještě `Order::where('customer_id', …)` — jméno sloupce\n";
echo "    jako řetězec v dotazu.\n\n";
echo "    Jména sloupců jsou vlastnosti objektu, takže se z modelu\n";
echo "    rozlezou do volajícího kódu — `\$each->customer_id`. Přejmenování\n";
echo "    v databázi pak není migrace, ale refaktoring napříč aplikací.\n";
echo "    Doména jména sloupců nezná vůbec; ví o nich jen mapper.\n\n";

// --- 6. Hybrid: Active Record jen jako persistence -------------------------

echo "6. Hybrid — Active Record vespod, doména nad ním\n\n";

$record = Order::find('2024/004');
$domain = new DomainStyle\Order(
    $record->number,
    $record->customer_id,
    (int) $record->total_cents,
    $record->status,
);

$domain->cancel();

$record->status = $domain->status();
$record->save();

printf("    pravidlo běželo v:     doméně (bez databáze)\n");
printf("    zápis obstaral:        Active Record\n");
printf("    v databázi:            %s\n\n", Order::find('2024/004')->format());

echo "    Tohle dělají větší Laravel projekty, když jim model\n";
echo "    přeroste. Není to zrada patternu — je to uznání, že\n";
echo "    tabulka a doména už nemají stejný tvar.\n";
