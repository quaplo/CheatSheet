<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka dlouhodobého refaktoringu.
 *
 * Táž přestavba udělaná dvakrát — jednou na odbočené větvi,
 * jednou v hlavní větvi po krocích mezi funkcemi.
 *
 * Spuštění:  php run.php
 */

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function commits(int $n): string
{
    return match (true) {
        $n === 1 => '1 commit',
        $n < 5 => $n . ' commity',
        default => $n . ' commitů',
    };
}

function git(string $repo, string $args): string
{
    return trim((string) shell_exec(sprintf(
        'git -C %s -c user.name=Demo -c user.email=demo@example.com -c commit.gpgsign=false %s 2>&1',
        escapeshellarg($repo),
        $args,
    )));
}

/** Nahradí obsah repozitáře daným stavem a udělá commit. */
function commitState(string $repo, string $state, string $message): void
{
    foreach (glob($repo . '/*.php') as $file) {
        unlink($file);
    }

    foreach (glob(__DIR__ . '/states/' . $state . '/*.php') as $file) {
        copy($file, $repo . '/' . basename($file));
    }

    git($repo, 'add -A');
    git($repo, 'commit -q -m ' . escapeshellarg($message));
}

function newRepo(): string
{
    $repo = sys_get_temp_dir() . '/long-term-' . bin2hex(random_bytes(4));
    mkdir($repo);
    git($repo, 'init -q');

    return $repo;
}

/** @return array{ok: bool, message: string} */
function check(string $repo): array
{
    $output = [];
    $code = 0;
    exec(sprintf('php %s %s 2>&1', escapeshellarg(__DIR__ . '/check.php'), escapeshellarg($repo)), $output, $code);

    // Cesta k dočasnému repozitáři je pokaždé jiná a v hlášce jen ruší.
    // (Na macOS hlásí PHP /private/var/… tam, kde je /var/… — proto obojí.)
    $message = str_replace([realpath($repo) . '/', $repo . '/'], '', implode(' ', $output));

    return ['ok' => $code === 0, 'message' => $message];
}

echo "=== Dlouhodobý refaktoring ===\n\n";

echo "    Cíl, na kterém se tým dohodl: přístup k datům přes\n";
echo "    OrderRepository místo statického LegacyDb. Práce na měsíce.\n";
echo "    Mezitím se dodávají funkce.\n\n";

echo "    (Měsíce jsou tu smrsknuté do několika commitů. Na počtu\n";
echo "    nezáleží — mechanismus je stejný pro jeden i pro sto.)\n\n";

// --- 1. Cesta A: odbočená větev -------------------------------------------

echo "1. Cesta A: přestavba na vlastní větvi\n\n";

$a = newRepo();
commitState($a, '00-vychozi', 'výchozí stav');
git($a, 'branch refactor');

// na hlavní větvi mezitím přibude funkce
commitState($a, 'f1-report', 'funkce: report a počet aktivních objednávek');

// na větvi se mezitím přestavuje
git($a, 'checkout -q refactor');
commitState($a, 'r1-vetev', 'refaktoring: přístup k datům přes OrderRepository');
git($a, 'checkout -q -');

$mainAhead = (int) git($a, 'rev-list --count refactor..HEAD');
$branchAhead = (int) git($a, 'rev-list --count HEAD..refactor');

printf("    %s%s\n", pad('hlavní větev je napřed o', 32), commits($mainAhead));
printf("    %s%s\n\n", pad('větev je napřed o', 32), commits($branchAhead));

$merge = git($a, 'merge --no-edit refactor');
$conflicted = array_filter(explode("\n", git($a, 'diff --name-only --diff-filter=U')));

printf("    %s%s\n", pad('git merge', 32), $conflicted === [] ? 'proběhl bez konfliktu' : count($conflicted) . 'x konflikt');

foreach ($conflicted as $file) {
    printf("      · %s\n", $file);
}

if ($conflicted !== []) {
    // Konflikt vyřešíme tak, jak to obvykle dopadne: vezmeme verzi z větve.
    foreach ($conflicted as $file) {
        git($a, 'checkout --theirs -- ' . escapeshellarg($file));
        git($a, 'add ' . escapeshellarg($file));
    }

    git($a, 'commit -q --no-edit');
    printf("    %s%s\n", pad('vyřešeno', 32), 'převzetím verze z větve');
}

$afterMerge = check($a);

printf("    %s%s\n\n", pad('kontrola po sloučení', 32), $afterMerge['ok'] ? 'prošla' : 'SPADLA');

if (!$afterMerge['ok']) {
    printf("      %s\n\n", $afterMerge['message']);

    echo "    Tohle je ta past. Git nenašel jediný konflikt, protože\n";
    echo "    každá strana sáhla na jiný soubor. OrderReport.php vznikl\n";
    echo "    na hlavní větvi a volá new OrderService() — jenže na větvi\n";
    echo "    mezitím konstruktor dostal parametr. Konflikt je významový\n";
    echo "    a ten git nevidí.\n\n";
}

// --- 2. Cesta B: v hlavní větvi -------------------------------------------

echo "2. Cesta B: přestavba v hlavní větvi, po krocích\n\n";

$b = newRepo();

$steps = [
    ['00-vychozi', 'výchozí stav', 'výchozí'],
    ['f1-report', 'funkce: report a počet aktivních objednávek', 'funkce'],
    ['r1-hlavni', 'refaktoring: přístup k datům přes OrderRepository', 'refaktoring'],
    ['f2-nejvetsi', 'funkce: největší aktivní objednávka', 'funkce'],
];

printf("    %s%s%s\n", pad('commit', 52), pad('druh', 15), 'kontrola');

$allGreen = true;

foreach ($steps as [$state, $message, $kind]) {
    commitState($b, $state, $message);
    $result = check($b);
    $allGreen = $allGreen && $result['ok'];

    printf("    %s%s%s\n", pad($message, 52), pad($kind, 15), $result['ok'] ? 'prošla' : 'SPADLA');
}

echo "\n";

printf("    %s%s\n", pad('commitů celkem', 32), commits(count($steps)));
printf("    %s%s\n\n", pad('kolikrát byla sada červená', 32), $allGreen ? '0' : 'alespoň jednou');

// --- 3. Vedle sebe ---------------------------------------------------------

echo "3. Vedle sebe\n\n";

$rows = [
    ['kdy má hlavní větev užitek', 'až po sloučení', 'hned po každém kroku'],
    ['konflikty, které git ohlásil', (string) count($conflicted), '0 — není co slučovat'],
    ['kód po sloučení', $afterMerge['ok'] ? 'funkční' : 'rozbitý', $allGreen ? 'funkční v každém commitu' : 'někde rozbitý'],
    ['kdy se chyba pozná', 'za měsíce, při slučování', 'v commitu, kde vznikla'],
];

printf("    %s%s%s\n", pad('', 32), pad('cesta A (větev)', 28), 'cesta B (hlavní větev)');

foreach ($rows as [$label, $left, $right]) {
    printf("    %s%s%s\n", pad($label, 32), pad($left, 28), $right);
}

echo "\n    Druhý řádek je to nejnebezpečnější číslo v téhle ukázce.\n";
echo "    Nula konfliktů neznamená, že je to v pořádku — znamená to,\n";
echo "    že se nikdo nemusel na nic podívat.\n\n";

foreach ([$a, $b] as $repo) {
    shell_exec('rm -rf ' . escapeshellarg($repo));
}

echo "    „Since all changes are refactorings, the code base can remain\n";
echo "     in a working state even as features are added.\"\n";
echo "                                       — Martin Fowler, 2014\n";
