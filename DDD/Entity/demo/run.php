<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Entity.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Domain/CustomerId.php';
require __DIR__ . '/Domain/EmailAddress.php';
require __DIR__ . '/Domain/LoyaltyTier.php';
require __DIR__ . '/Domain/Customer.php';
require __DIR__ . '/Before/AnemicCustomer.php';
require __DIR__ . '/Before/CustomerService.php';

use Before\AnemicCustomer;
use Before\CustomerService;
use Domain\Customer;
use Domain\CustomerId;
use Domain\EmailAddress;

echo "=== Entity ===\n\n";

// --- 1. Identita přežije změnu všeho ostatního ----------------------------

echo "1. Identita přežije změnu všech atributů\n";

$id = CustomerId::fromString('CUST-4711');

$customer = Customer::register($id, EmailAddress::fromString('info@pekarna.cz'), 'Pekárna Novák s.r.o.');

printf("    při registraci:  %s · %s · %d b · %s\n",
    $customer->id->value, $customer->email()->value, $customer->points(), $customer->tier()->value);

$customer->rename('Pekárny Novák a.s.');
$customer->changeEmail(EmailAddress::fromString('  Fakturace@PekarnyNovak.CZ '));
$customer->earnPoints(6200);

printf("    o dva roky poz.: %s · %s · %d b · %s\n",
    $customer->id->value, $customer->email()->value, $customer->points(), $customer->tier()->value);

echo "\n    Jméno jiné, e-mail jiný, body jiné, úroveň jiná.\n";
echo "    Pořád je to týž zákazník — protože identita se nezměnila.\n";

// --- 2. Rovnost podle identity, ne podle obsahu ---------------------------

echo "\n2. Rovnost podle identity\n";

// Táž entita načtená v jiném stavu — třeba starší verze z cache.
$sameCustomerOlderState = Customer::reconstitute(
    CustomerId::fromString('CUST-4711'),
    EmailAddress::fromString('info@pekarna.cz'),
    'Pekárna Novák s.r.o.',
    loyaltyPoints: 0,
    isActive: true,
);

$differentCustomer = Customer::register(
    CustomerId::generate(),
    EmailAddress::fromString('info@pekarna.cz'),
    'Pekárna Novák s.r.o.',          // úplně stejné atributy!
);

printf('    $a === $b  (jiné instance téhož zákazníka)  %s' . "\n", $customer === $sameCustomerOlderState ? 'true' : 'false');
printf("    equals() se starší verzí téhož   %s   ← na obsahu nezáleží\n", $customer->equals($sameCustomerOlderState) ? 'true' : 'false');
printf("    equals() s jiným, ale identickým  %s   ← stejné atributy nestačí\n", $customer->equals($differentCustomer) ? 'true' : 'false');

echo "\n    U value objectu by to bylo přesně naopak.\n";

// --- 3. Odvozený stav se nemůže rozejít -----------------------------------

echo "\n3. Odvozený stav se nemůže rozejít\n";

$anemic = new AnemicCustomer('CUST-9000', 'a@b.cz', 'Firma', 0, 'bronz', true);
$service = new CustomerService();

$service->addPoints($anemic, 6200);
printf("    anemický po service->addPoints():  %d b → %s   sleva %d %%\n",
    $anemic->getLoyaltyPoints(), $anemic->getTier(), $service->discountFor($anemic));

// A teď to, co se v praxi opravdu stane: někdo body nastaví jinudy.
$anemic->setLoyaltyPoints(200);
printf("    po přímém setLoyaltyPoints(200):   %d b → %s   sleva %d %%   ← ROZPOR\n",
    $anemic->getLoyaltyPoints(), $anemic->getTier(), $service->discountFor($anemic));

$rich = Customer::register(CustomerId::generate(), EmailAddress::fromString('a@b.cz'), 'Firma');
$rich->earnPoints(6200);
printf("\n    entita po earnPoints(6200):        %d b → %s   sleva %d %%\n",
    $rich->points(), $rich->tier()->value, $rich->discountPercent());
$rich->redeemPoints(6000);
printf("    po redeemPoints(6000):             %d b → %s   sleva %d %%   ← dopočítáno\n",
    $rich->points(), $rich->tier()->value, $rich->discountPercent());

echo "\n    Úroveň se nikde neukládá, vyplývá z bodů. Nemá jak se rozejít.\n";

// --- 4. Tell, Don't Ask ----------------------------------------------------

echo "\n4. Tell, Don't Ask\n\n";
echo "    anemický:  if (\$c->isActive() && \$c->getTier() === 'zlato') { \$d = 10; }\n";
echo "               …a totéž ještě v košíku, v ceníku a v exportu\n\n";
echo "    entita:    \$discount = \$customer->discountPercent();\n";

// --- 5. Entita si hlídá svá pravidla --------------------------------------

echo "\n5. Neplatnou operaci entita nepustí\n";

try {
    $rich->redeemPoints(999999);
} catch (LogicException $e) {
    printf("    %s\n", $e->getMessage());
}

$rich->deactivate();

try {
    $rich->earnPoints(100);
} catch (LogicException $e) {
    printf("    %s\n", $e->getMessage());
}

printf("    sleva neaktivního zákazníka: %d %%\n", $rich->discountPercent());

echo "\n    Anemická entita by všechno tohle pustila — nemá čím bránit.\n";
