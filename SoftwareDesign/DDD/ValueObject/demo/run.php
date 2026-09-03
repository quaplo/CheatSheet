<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Value Object.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Currency.php';
require __DIR__ . '/Money.php';
require __DIR__ . '/EmailAddress.php';
require __DIR__ . '/Country.php';
require __DIR__ . '/PostalCode.php';
require __DIR__ . '/Address.php';

echo "=== Value Object ===\n\n";

// --- 1. Rovnost podle hodnoty, ne podle instance ---------------------------

echo "1. Rovnost podle hodnoty\n";

$a = Money::fromCents(129000, Currency::CZK);
$b = Money::fromCents(129000, Currency::CZK);

printf("    %s vs %s\n", $a->format(), $b->format());
printf('    $a === $b  (táž instance?)  %s' . "\n", $a === $b ? 'true' : 'false');
printf("    \$a->equals(\$b)             %s   ← tohle nás zajímá\n\n", $a->equals($b) ? 'true' : 'false');

// --- 2. Neměnnost ----------------------------------------------------------

echo "2. Neměnnost\n";

$price = Money::fromCents(129000, Currency::CZK);
$withShipping = $price->add(Money::fromCents(9900, Currency::CZK));

printf("    původní:      %s\n", $price->format());
printf("    s dopravou:   %s   ← nová instance, původní se nezměnila\n\n", $withShipping->format());

// --- 3. Objekt se hlídá sám ------------------------------------------------

echo "3. Neplatná operace neprojde\n";

try {
    Money::fromCents(129000, Currency::CZK)->add(Money::fromCents(5000, Currency::EUR));
} catch (InvalidArgumentException $e) {
    echo '    Chyba: ' . $e->getMessage() . "\n\n";
}

// --- 4. Chování, které patří k hodnotě -------------------------------------

echo "4. Rozdělení částky bez ztráty haléře\n";

$total = Money::fromCents(10000, Currency::CZK);
$parts = $total->allocate(3);

$sum = Money::zero(Currency::CZK);

foreach ($parts as $i => $part) {
    printf("    díl %d:  %s\n", $i + 1, $part->format());
    $sum = $sum->add($part);
}

printf("    součet: %s   ← naivní dělení by ztratilo haléř\n\n", $sum->format());

// --- 5. Normalizace na jednom místě ----------------------------------------

echo "5. Normalizace\n";

$email = EmailAddress::fromString('  Alice@Example.COM ');
$same = EmailAddress::fromString('alice@example.com');

printf("    zadáno:  „  Alice@Example.COM “\n");
printf("    uloženo: „%s“\n", $email->value);
printf("    doména:  %s\n", $email->domain());
printf("    shoda s „alice@example.com“: %s\n\n", $email->equals($same) ? 'true' : 'false');

// --- 6. Neplatná instance nevznikne ----------------------------------------

echo "6. Neplatná instance nevznikne\n";

try {
    EmailAddress::fromString('rozhodne-neni-email');
} catch (InvalidArgumentException $e) {
    echo '    Chyba: ' . $e->getMessage() . "\n";
}

echo "\n";

// --- 7. Kompozitní value object -------------------------------------------

echo "7. Kompozitní value object\n";

$address = Address::create(
    street: '  Sokolovská 100  ',
    city: 'Praha',
    postalCode: PostalCode::fromString('186 00', Country::CZ),
    country: Country::CZ,
);

foreach (explode("\n", $address->format()) as $line) {
    echo '    ' . $line . "\n";
}

$same = Address::create(
    street: 'Sokolovská 100',
    city: 'Praha',
    postalCode: PostalCode::fromString('18600', Country::CZ),
    country: Country::CZ,
);

printf("    shoda s „18600“ bez mezery: %s   ← rovnost deleguje na složky\n\n", $address->equals($same) ? 'true' : 'false');

// --- 8. Invariant, který žádná složka sama neuhlídá ------------------------

echo "8. Invariant napříč složkami\n";

$czechPostalCode = PostalCode::fromString('186 00', Country::CZ);
printf("    PSČ %s je platné.\n", $czechPostalCode->format());
printf("    Země %s je platná.\n", Country::SK->value);

try {
    Address::create('Hlavná 1', 'Bratislava', $czechPostalCode, Country::SK);
} catch (InvalidArgumentException $e) {
    echo '    Chyba: ' . $e->getMessage() . "\n";
}

echo "           ↑ obě složky platné, jejich kombinace ne\n";
