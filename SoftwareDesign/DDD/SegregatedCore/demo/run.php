<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Segregated Core.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Before/Mailer.php';
require __DIR__ . '/Before/CurrencyConverter.php';
require __DIR__ . '/Before/CountryRegistry.php';
require __DIR__ . '/Before/Order.php';

require __DIR__ . '/After/Core/OrderStatus.php';
require __DIR__ . '/After/Core/OrderItem.php';
require __DIR__ . '/After/Core/Order.php';
require __DIR__ . '/After/Support/CountryRegistry.php';
require __DIR__ . '/After/Support/CurrencyConverter.php';
require __DIR__ . '/After/Support/Mailer.php';
require __DIR__ . '/After/Support/OrderFormatter.php';
require __DIR__ . '/After/Support/OrderExporter.php';
require __DIR__ . '/After/Support/OrderNotifier.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/**
 * Na kterých cizích třídách třída závisí — z konstruktoru a z typů metod.
 *
 * @return list<string>
 */
function dependenciesOf(string $class): array
{
    $found = [];
    $reflection = new ReflectionClass($class);

    $constructor = $reflection->getConstructor();
    $params = $constructor !== null ? $constructor->getParameters() : [];

    foreach ($reflection->getMethods() as $method) {
        if ($method->getDeclaringClass()->getName() !== $class) {
            continue;
        }

        $params = [...$params, ...$method->getParameters()];
    }

    foreach ($params as $param) {
        $type = $param->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $found[] = $type->getName();
        }
    }

    return array_values(array_unique($found));
}

/** Počet metod deklarovaných přímo ve třídě, bez konstruktoru. */
function methodCount(string $class): int
{
    return count(array_filter(
        (new ReflectionClass($class))->getMethods(),
        static fn (ReflectionMethod $m): bool
            => $m->getDeclaringClass()->getName() === $class && !$m->isConstructor(),
    ));
}

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

echo "=== Segregated Core ===\n\n";

// --- 1. Co jádro ví o okolí ------------------------------------------------

echo "1. Na čem jádro závisí\n\n";

$beforeDeps = dependenciesOf(Before\Order::class);
$afterDeps = array_filter(
    dependenciesOf(After\Core\Order::class),
    static fn (string $c): bool => !str_starts_with($c, 'After\\Core'),
);

printf("    %s%s\n", pad('', 26), 'závislostí na jiných třídách');
printf("    %s%d\n", pad('Before\\Order', 26), count($beforeDeps));

foreach ($beforeDeps as $dep) {
    echo '        ' . $dep . "\n";
}

printf("    %s%d   ← jádro nezná nikoho\n\n", pad('After\\Core\\Order', 26), count($afterDeps));

printf(
    "    metod ve třídě:        Before %d  ·  After %d\n",
    methodCount(Before\Order::class),
    methodCount(After\Core\Order::class),
);
printf(
    "    řádků kódu:            Before %d  ·  After %d\n\n",
    codeLines(__DIR__ . '/Before/Order.php'),
    codeLines(__DIR__ . '/After/Core/Order.php'),
);

// --- 2. Kolik parametrů potřebuje objednávka vzniknout ---------------------

echo "2. Kolik je potřeba, aby objednávka vůbec vznikla\n\n";

$beforeCtor = (new ReflectionClass(Before\Order::class))->getConstructor();
$afterCtor = (new ReflectionClass(After\Core\Order::class))->getConstructor();

printf("    %s%d parametrů\n", pad('Before\\Order', 26), $beforeCtor->getNumberOfParameters());
printf("    %s%d parametr    ← jen číslo objednávky\n\n", pad('After\\Core\\Order', 26), $afterCtor->getNumberOfParameters());

echo "    Rozdíl je vidět v testu: jádro postavíš jedním řádkem,\n";
echo "    zamotanou verzi až po sestavení tří spolupracovníků.\n\n";

// --- 3. Směr závislostí ----------------------------------------------------

echo "3. Závislosti vedou jedním směrem\n\n";

$coreFiles = glob(__DIR__ . '/After/Core/*.php');
$supportFiles = glob(__DIR__ . '/After/Support/*.php');

$coreKnowsSupport = 0;

foreach ($coreFiles as $file) {
    $coreKnowsSupport += substr_count(file_get_contents($file), 'After\\Support');
}

$supportKnowsCore = 0;

foreach ($supportFiles as $file) {
    $supportKnowsCore += substr_count(file_get_contents($file), 'After\\Core');
}

printf("    %s%d\n", pad('Core → Support', 26), $coreKnowsSupport);
printf("    %s%d\n\n", pad('Support → Core', 26), $supportKnowsCore);

echo "    Support ví o jádru. Jádro o Supportu nikoli — a právě tohle\n";
echo "    je ten „reducing its coupling to other code\" z definice.\n\n";

// --- 4. Chování zůstalo stejné ---------------------------------------------

echo "4. Chování se nezměnilo\n\n";

$mailer = new Before\Mailer();
$old = new Before\Order(
    '2026/001',
    'alice@example.com',
    'CZ',
    new Before\CurrencyConverter(),
    $mailer,
    new Before\CountryRegistry(),
);
$old->addItem('MON-27', 799000, 1);
$old->addItem('KLA-01', 249000, 2);
$old->confirm();
$old->cancel();

$new = new After\Core\Order('2026/001');
$new->addItem(new After\Core\OrderItem('MON-27', 799000, 1));
$new->addItem(new After\Core\OrderItem('KLA-01', 249000, 2));
$new->confirm();
$new->cancel();

$newMailer = new After\Support\Mailer();
$formatter = new After\Support\OrderFormatter();
$notifier = new After\Support\OrderNotifier($newMailer, $formatter);
$notifier->notifyConfirmed($new, 'alice@example.com');
$notifier->notifyCancelled($new, 'alice@example.com');

printf("    %s%s\n", pad('', 26), pad('Before', 22) . 'After');
printf("    %s%s%s\n", pad('celkem', 26), pad(number_format($old->totalInCents() / 100, 2, ',', ' ') . ' Kč', 22), $formatter->formatTotal($new));
printf("    %s%s%s\n", pad('stav', 26), pad($old->status(), 22), $new->status()->value);
printf("    %s%s%s\n\n", pad('odeslaných e-mailů', 26), pad((string) count($mailer->sent), 22), (string) count($newMailer->sent));

// --- 5. Jádro se dá otestovat bez čehokoli dalšího -------------------------

echo "5. Jádro v testu\n\n";

echo "    \$order = new Order('2026/002');\n";
echo "    \$order->addItem(new OrderItem('MON-27', 799000, 1));\n";
echo "    \$order->cancel();\n\n";

$test = new After\Core\Order('2026/002');
$test->addItem(new After\Core\OrderItem('MON-27', 799000, 1));
$test->cancel();

printf("    stav:                  %s\n", $test->status()->value);
printf("    odeslané e-maily:      0   ← žádný mailer se nesestavoval\n\n");

echo "    Před oddělením by tenhle test potřeboval mailer, převodník\n";
echo "    měn i číselník zemí — a při zrušení by odeslal e-mail.\n";
