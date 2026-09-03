<?php

declare(strict_types=1);

/**
 * Value object, který máš v PHP nativně.
 *
 * Spuštění:  php native-datetime.php
 *
 * DateTimeImmutable splňuje všech pět vlastností value objectu.
 * DateTime je až na neměnnost totéž — a právě proto je poučný.
 */

echo "=== DateTimeImmutable: value object, který už znáš ===\n\n";

echo "1. Rovnost podle hodnoty, ne podle instance\n";

$a = new DateTimeImmutable('2026-09-01 10:00:00');
$b = new DateTimeImmutable('2026-09-01 10:00:00');

printf("    \$a === \$b   %s   ← jiné instance\n", $a === $b ? 'true' : 'false');
printf("    \$a == \$b    %s   ← tentýž okamžik\n", $a == $b ? 'true' : 'false');
printf("    \$a < zítra  %s\n\n", $a < new DateTimeImmutable('2026-09-02') ? 'true' : 'false');

echo "2. Neměnnost\n";

$start = new DateTimeImmutable('2026-09-01');
$end = $start->modify('+1 day');

printf("    původní: %s   ← beze změny\n", $start->format('Y-m-d'));
printf("    nový:    %s\n\n", $end->format('Y-m-d'));

echo "3. Táž operace na měnitelném DateTime\n";

$mutableStart = new DateTime('2026-09-01');
$mutableEnd = $mutableStart->modify('+1 day');

printf("    původní: %s   ← původní datum je pryč\n", $mutableStart->format('Y-m-d'));
printf("    „nový“:  %s\n", $mutableEnd->format('Y-m-d'));
printf("    jsou to tytéž objekty? %s\n\n", $mutableStart === $mutableEnd ? 'true' : 'false');

echo "4. A protože se objekty předávají odkazem…\n";

$createdAt = new DateTime('2026-09-01');
$deadline = $createdAt;   // žádná kopie, tatáž instance
$deadline->modify('+1 month');

printf("    \$deadline:  %s\n", $deadline->format('Y-m-d'));
printf("    \$createdAt: %s   ← změnilo se něco, čeho ses ani nedotkl\n\n", $createdAt->format('Y-m-d'));

echo "5. Neplatná hodnota nevznikne\n";

try {
    new DateTimeImmutable('rozhodne-neni-datum');
} catch (Throwable $e) {
    printf("    %s\n", $e::class);
}

echo "\nZávěr: v novém kódu piš DateTimeImmutable. A když píšeš vlastní\n";
echo "value object, dělej to, co dělá on — readonly a new self() místo změny \$this.\n";
