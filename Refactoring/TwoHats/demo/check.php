<?php

declare(strict_types=1);

/**
 * Kontrola pro `git bisect run`.
 *
 * Ověřuje chování, které platilo od prvního commitu: malý košík
 * se třemi položkami po 12,50 Kč stojí s DPH 45,38 Kč.
 *
 * Návratový kód 0 = v pořádku, 1 = rozbité. Nic víc bisect nepotřebuje.
 */

require ($argv[1] ?? getcwd()) . '/Cart.php';

$cart = new Cart();
$cart->add(1250);
$cart->add(1250);
$cart->add(1250);

exit($cart->total() === 4538 ? 0 : 1);
