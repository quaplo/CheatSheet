<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka pravidla dvou klobouků.
 *
 * Postaví dvě skutečné git historie se stejným výsledným kódem —
 * liší se jen tím, kde jsou hranice commitů — a v obou nechá
 * `git bisect` najít tutéž chybu.
 *
 * Spuštění:  php run.php
 */

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function lines(int $n): string
{
    return match (true) {
        $n === 1 => '1 řádek',
        $n < 5 => $n . ' řádky',
        default => $n . ' řádků',
    };
}

function git(string $repo, string $args): string
{
    $cmd = sprintf(
        'git -C %s -c user.name=Demo -c user.email=demo@example.com -c commit.gpgsign=false %s 2>&1',
        escapeshellarg($repo),
        $args,
    );

    return trim((string) shell_exec($cmd));
}

/**
 * Postaví repozitář z posloupnosti verzí souboru Cart.php.
 *
 * @param list<array{0: string, 1: string}> $commits dvojice [soubor s verzí, zpráva commitu]
 */
function buildRepo(array $commits): string
{
    $repo = sys_get_temp_dir() . '/two-hats-' . bin2hex(random_bytes(4));
    mkdir($repo);
    git($repo, 'init -q');

    foreach ($commits as [$version, $message]) {
        copy(__DIR__ . '/versions/' . $version, $repo . '/Cart.php');
        git($repo, 'add Cart.php');
        git($repo, 'commit -q -m ' . escapeshellarg($message));
    }

    return $repo;
}

/** Spustí bisect a vrátí commit, na který ukázal, i velikost jeho diffu. */
function bisect(string $repo): array
{
    $first = git($repo, 'rev-list --max-parents=0 HEAD');

    git($repo, 'bisect start HEAD ' . $first);
    $output = git($repo, sprintf(
        'bisect run php %s %s',
        escapeshellarg(__DIR__ . '/check.php'),
        escapeshellarg($repo),
    ));
    git($repo, 'bisect reset');

    preg_match('/([0-9a-f]{7,40}) is the first bad commit/', $output, $m);
    $sha = $m[1] ?? '';

    $subject = git($repo, 'log -1 --format=%s ' . escapeshellarg($sha));
    $numstat = git($repo, 'show --numstat --format= ' . escapeshellarg($sha));

    [$added, $removed] = array_map('intval', explode("\t", $numstat));

    return ['sha' => $sha, 'subject' => $subject, 'changed' => $added + $removed];
}

echo "=== Dva klobouky ===\n\n";

echo "    „You can only wear one hat at a time.\"\n";
echo "                                       — Kent Beck, přes Martina Fowlera\n\n";

echo "    Úkol: přidat slevu pro velkoodběratele. Kód kolem toho\n";
echo "    potřebuje úklid. Uděláme to dvakrát — jednou v jednom\n";
echo "    commitu, jednou ve dvou.\n\n";

// --- 1. Dvě historie, jeden výsledek --------------------------------------

echo "1. Dvě historie se stejným koncem\n\n";

$mixed = buildRepo([
    ['01-zaklad.php', 'Cart: základ s DPH'],
    ['02-pocet.php', 'Cart: počet položek'],
    ['03-bez-dph.php', 'Cart: součet bez DPH'],
    ['05-sleva.php', 'Cart: sleva pro velkoodběratele + úklid'],
    ['06-prazdny.php', 'Cart: prázdný košík'],
]);

$separated = buildRepo([
    ['01-zaklad.php', 'Cart: základ s DPH'],
    ['02-pocet.php', 'Cart: počet položek'],
    ['03-bez-dph.php', 'Cart: součet bez DPH'],
    ['04-uklid.php', 'Cart: úklid před slevou (nemění chování)'],
    ['05-sleva.php', 'Cart: sleva pro velkoodběratele'],
    ['06-prazdny.php', 'Cart: prázdný košík'],
]);

$sameCode = md5_file($mixed . '/Cart.php') === md5_file($separated . '/Cart.php');

printf("    %s%s\n", pad('smíchané klobouky', 24), git($mixed, 'rev-list --count HEAD') . ' commitů');
printf("    %s%s\n", pad('oddělené klobouky', 24), git($separated, 'rev-list --count HEAD') . ' commitů');
printf("    %s%s\n\n", pad('výsledný Cart.php', 24), $sameCode ? 'bajt po bajtu totožný' : 'LIŠÍ SE — ukázka je rozbitá');

echo "    Stejný kód, stejná práce. Jediný rozdíl je v tom, kudy\n";
echo "    vedou hranice commitů.\n\n";

// --- 2. Bisect -------------------------------------------------------------

echo "2. Za týden se ozve zákaznická podpora\n\n";

echo "    Malý košík počítá špatně. `git bisect` hledá viníka:\n\n";

$a = bisect($mixed);
$b = bisect($separated);

printf("    %s%s%s\n", pad('historie', 24), pad('bisect ukázal na', 44), 'k přečtení');
printf("    %s%s%s\n", pad('smíchané klobouky', 24), pad($a['subject'], 44), lines($a['changed']));
printf("    %s%s%s\n\n", pad('oddělené klobouky', 24), pad($b['subject'], 44), lines($b['changed']));

printf(
    "    Chyba je v obou případech tatáž jedna podmínka. V prvním\n    případě ji hledáš mezi %d, ve druhém mezi %d.\n\n",
    $a['changed'],
    $b['changed'],
);

// --- 3. Co ten úklidový commit vlastně udělal ------------------------------

echo "3. Co udělal úklidový commit\n\n";

$cleanupSha = git($separated, "log --format=%H --grep='úklid'");
git($separated, 'checkout -q ' . escapeshellarg($cleanupSha));
$cleanupPasses = shell_exec(sprintf(
    'php %s %s > /dev/null 2>&1; echo $?',
    escapeshellarg(__DIR__ . '/check.php'),
    escapeshellarg($separated),
)) === "0\n";
git($separated, 'checkout -q -');

printf("    %s%s\n\n", pad('kontrola nad úklidovým commitem', 36), $cleanupPasses ? 'prošla' : 'SPADLA');

echo "    Proto ho bisect přeskočil. Refaktoring nemění chování —\n";
echo "    a když se drží ve vlastním commitu, dá se to ověřit.\n\n";

// --- 4. Vrácení ------------------------------------------------------------

echo "4. A co když se to má vrátit\n\n";

/** Vrátí daný commit na odbočce a přečte, co ve výsledku zbylo. */
function afterRevert(string $repo, string $sha): array
{
    git($repo, 'checkout -q -b revert-test ' . escapeshellarg($sha));
    git($repo, 'revert --no-edit HEAD');

    $code = git($repo, 'show HEAD:Cart.php');

    git($repo, 'checkout -q -');
    git($repo, 'branch -q -D revert-test');

    return [
        'cleanup' => str_contains($code, 'pricesInCents'),
        'feature' => str_contains($code, 'discounted'),
    ];
}

$afterMixed = afterRevert($mixed, $a['sha']);
$afterSeparated = afterRevert($separated, $b['sha']);

printf("    %s%s%s\n", pad('po vrácení viníka', 24), pad('sleva pryč?', 16), 'úklid zůstal?');
printf(
    "    %s%s%s\n",
    pad('smíchané klobouky', 24),
    pad($afterMixed['feature'] ? 'ne' : 'ano', 16),
    $afterMixed['cleanup'] ? 'ano' : 'NE — přišel jsi i o něj',
);
printf(
    "    %s%s%s\n\n",
    pad('oddělené klobouky', 24),
    pad($afterSeparated['feature'] ? 'ne' : 'ano', 16),
    $afterSeparated['cleanup'] ? 'ano' : 'NE — přišel jsi i o něj',
);

echo "    Refaktoring se dá zahodit bez následků, protože nic nemění.\n";
echo "    Jakmile je slepený se změnou chování, nedá se zahodit nic —\n";
echo "    vrácení jedné vady s sebou vezme i všechnu úklidovou práci.\n\n";

// --- úklid -----------------------------------------------------------------

foreach ([$mixed, $separated] as $repo) {
    shell_exec('rm -rf ' . escapeshellarg($repo));
}

echo "    „When refactoring every change you make is a small\n";
echo "     behavior-preserving change. You only refactor with green\n";
echo "     tests, and any test failing indicates a mistake.\"\n";
echo "                                       — Martin Fowler, 2014\n";
