<?php

declare(strict_types=1);

/**
 * Kontrola stavu repozitáře.
 *
 * Ověřuje chování, které má platit po celou dobu přestavby:
 * dvě aktivní objednávky za 20 000 haléřů. Návratový kód 0 = v pořádku.
 *
 * Objekty sestavuje přes reflexi, protože se během přestavby mění
 * jejich konstruktory — a právě to je na tom to zajímavé.
 */

$repo = $argv[1] ?? getcwd();

spl_autoload_register(static function (string $class) use ($repo): void {
    $file = $repo . '/' . $class . '.php';

    if (is_file($file)) {
        require $file;
    }
});

/** Sestaví objekt a dodá mu, co si konstruktor řekne. */
function build(string $class): object
{
    $constructor = (new ReflectionClass($class))->getConstructor();

    if ($constructor === null) {
        return new $class();
    }

    $arguments = [];

    foreach ($constructor->getParameters() as $parameter) {
        $type = (string) $parameter->getType();
        $arguments[] = build($type === 'OrderRepository' ? 'LegacyOrderRepository' : $type);
    }

    return new $class(...$arguments);
}

try {
    $service = build('OrderService');

    if ($service->activeTotal() !== 20000) {
        echo 'activeTotal() vrací ' . $service->activeTotal() . ", čekáno 20000\n";
        exit(1);
    }

    if (is_file($repo . '/OrderReport.php')) {
        $summary = build('OrderReport')->summary();

        if (!str_contains($summary, '20000') || !str_contains($summary, '2 objednávek')) {
            echo 'OrderReport::summary() vrací „' . $summary . "\"\n";
            exit(1);
        }
    }
} catch (Throwable $e) {
    echo $e::class . ': ' . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
