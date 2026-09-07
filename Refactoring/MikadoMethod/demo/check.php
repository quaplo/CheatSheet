<?php

declare(strict_types=1);

/**
 * Kontrola stavu kódu.
 *
 * Sestaví oba vstupní body tak, jak by to udělalo skutečné místo
 * sestavení aplikace, a projde jimi celý řetěz volání. Uvnitř už
 * si třídy vytvářejí spolupracovníky samy — a právě tam se láme,
 * když někomu přibude parametr konstruktoru.
 *
 * Návratový kód 0 = v pořádku, 1 = rozbité.
 */

$dir = $argv[1] ?? getcwd();

spl_autoload_register(static function (string $class) use ($dir): void {
    $file = $dir . '/' . $class . '.php';

    if (is_file($file)) {
        require $file;
    }
});

/** Místo sestavení smí o konfiguraci vědět — dodá, co si konstruktor řekne. */
function root(string $class): object
{
    $constructor = (new ReflectionClass($class))->getConstructor();
    $arguments = array_fill(0, $constructor?->getNumberOfParameters() ?? 0, 21);

    return new $class(...$arguments);
}

/**
 * Každý vstupní bod se zkouší zvlášť. Mikado potřebuje seznam
 * všeho, co se rozbilo — ne jen první nález.
 *
 * @var list<array{0: string, 1: callable(object): string, 2: string}> $roots
 */
$roots = [
    ['OrderReport', static fn (object $r): string => $r->summary(10000), 'Objednávka za 12100 haléřů'],
    ['Invoice', static fn (object $i): string => $i->line(10000), 'Celkem 12100 haléřů'],
];

$errors = [];

foreach ($roots as [$class, $call, $expected]) {
    try {
        $actual = $call(root($class));

        if ($actual !== $expected) {
            $errors[] = $class . ' vrací „' . $actual . '"';
        }
    } catch (Throwable $e) {
        $errors[] = $e::class . ': ' . $e->getMessage();
    }
}

if ($errors !== []) {
    echo implode("\n", $errors) . "\n";
    exit(1);
}

exit(0);
