<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka vrstvené architektury.
 *
 * Spuštění:  php run.php
 */

foreach (['Naive', 'Layered', 'Inverted'] as $variant) {
    foreach (glob(__DIR__ . '/' . $variant . '/{,*/}*.php', GLOB_BRACE) as $file) {
        require_once $file;
    }
}

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

/** Na které vrstvy daná vrstva sahá. */
function dependenciesOf(string $dir): array
{
    $result = [];

    foreach (glob($dir . '/*', GLOB_ONLYDIR) as $layerDir) {
        $layer = basename($layerDir);
        $result[$layer] = [];

        foreach (glob($layerDir . '/*.php') as $file) {
            preg_match_all('/^use\s+([^;]+);/m', file_get_contents($file), $m);

            foreach ($m[1] as $import) {
                $target = explode('\\', $import)[1] ?? null;

                if ($target !== null && $target !== $layer) {
                    $result[$layer][$target] = true;
                }
            }
        }

        $result[$layer] = array_keys($result[$layer]);
    }

    return $result;
}

/** Zkusí spočítat celkovou cenu bez jakékoli infrastruktury. */
function tryWithoutInfrastructure(callable $attempt): string
{
    try {
        return 'prošlo, vyšlo ' . $attempt() . ' haléřů';
    } catch (Throwable $e) {
        return 'SPADLO — ' . $e->getMessage();
    }
}

echo "=== Vrstvená architektura ===\n\n";

// --- 1. Bez vrstev ---------------------------------------------------------

echo "1. Než se to rozdělí\n\n";

echo "    Jedna třída: čtení z databáze, pravidlo o slevě\n";
echo "    i HTML na výstupu.\n\n";

printf(
    "    %s%s\n\n",
    pad('spočítat slevu bez databáze', 34),
    tryWithoutInfrastructure(static fn (): string => (new Naive\OrderReport())->render('A-1', 1)),
);

echo "    Evans to popisuje přesně takhle: „UI, database, and other\n";
echo "    support code often gets written directly into the business\n";
echo "    objects. […] Automated testing is awkward.\"\n\n";

// --- 2. Vrstvy podle učebnice ---------------------------------------------

echo "2. Rozdělíme to na vrstvy\n\n";

foreach (dependenciesOf(__DIR__ . '/Layered') as $layer => $targets) {
    printf("    %s%s\n", pad($layer, 18), $targets === [] ? '—' : '→ ' . implode(', ', $targets));
}

echo "\n    Závislosti míří dolů, pravidlo vrstev platí. UI o doméně\n";
echo "    neví a změna šablony už doménu nerozbije.\n\n";

printf(
    "    %s%s\n\n",
    pad('spočítat slevu bez databáze', 34),
    tryWithoutInfrastructure(static fn (): string => (string) (new Layered\Domain\OrderTotals())->totalFor('A-1', 1)),
);

echo "    A přesto to pořád nejde. Doména sahá na infrastrukturu —\n";
echo "    a podle pravidla vrstev SMÍ, protože infrastruktura je níž.\n";
echo "    Vrstvy oddělily doménu od uživatelského rozhraní, ale ne\n";
echo "    od databáze.\n\n";

// --- 3. Otočená závislost -------------------------------------------------

echo "3. Otočíme jednu šipku\n\n";

foreach (dependenciesOf(__DIR__ . '/Inverted') as $layer => $targets) {
    printf("    %s%s\n", pad($layer, 18), $targets === [] ? '—' : '→ ' . implode(', ', $targets));
}

echo "\n    Domain nemá jedinou odchozí závislost. Rozhraní OrderLines\n";
echo "    si definuje sama doména a infrastruktura ho naplňuje.\n\n";

/** Testovací dvojník, který v doméně vznikne za tři řádky. */
final class FakeOrderLines implements Inverted\Domain\OrderLines
{
    /** @return list<Inverted\Domain\OrderLine> */
    public function of(string $orderId): array
    {
        return [new Inverted\Domain\OrderLine(12900, 2), new Inverted\Domain\OrderLine(4900, 1)];
    }
}

printf(
    "    %s%s\n\n",
    pad('spočítat slevu bez databáze', 34),
    tryWithoutInfrastructure(
        static fn (): string => (string) (new Inverted\Domain\OrderTotals(new FakeOrderLines()))->totalFor('A-1', 1),
    ),
);

// --- 4. Co se tím vlastně stalo -------------------------------------------

echo "4. Co se tím stalo\n\n";

$layered = dependenciesOf(__DIR__ . '/Layered');
$inverted = dependenciesOf(__DIR__ . '/Inverted');

printf("    %s%s%s\n", pad('', 42), pad('podle učebnice', 18), 's otočenou šipkou');
printf(
    "    %s%s%s\n",
    pad('odchozích závislostí z Domain', 42),
    pad((string) count($layered['Domain']), 18),
    (string) count($inverted['Domain']),
);
printf(
    "    %s%s%s\n",
    pad('doména testovatelná bez infrastruktury', 42),
    pad('ne', 18),
    'ano',
);
printf(
    "    %s%s%s\n\n",
    pad('pravidlo „závislosti míří dolů“', 42),
    pad('platí', 18),
    'platí',
);

echo "    Poslední řádek je ta pointa. Pravidlo vrstev platí v obou\n";
echo "    případech — takže samo o sobě nestačí. Rozhoduje, KTERÁ\n";
echo "    vrstva je vlastně dole.\n\n";

echo "    Evans to na konci svého textu přiznává sám:\n\n";
echo "    „The key goal here is isolation. Related patterns, such as\n";
echo "     ‚Hexagonal Architecture' may serve AS WELL OR BETTER to the\n";
echo "     degree that they allow our domain model expressions to avoid\n";
echo "     dependencies on and references to other system concerns.\"\n";
