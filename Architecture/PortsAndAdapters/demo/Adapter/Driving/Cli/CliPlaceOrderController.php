<?php

declare(strict_types=1);

namespace Adapter\Driving\Cli;

use Core\Port\Driving\PaymentDeclined;
use Core\Port\Driving\PlaceOrder;
use Core\Port\Driving\PlaceOrderCommand;

/**
 * Řídicí (primary) adaptér.
 *
 * Jeho jediná práce: přeložit podnět zvenčí do jazyka jádra a výsledek
 * zpátky ven. Žádná byznys logika — kdyby tady byla podmínka o tom, kdy
 * se objednávka smí založit, patřila by do jádra.
 *
 * Kdyby vedle vznikl HTTP controller nebo consumer fronty, vypadaly by
 * stejně: parsuj → zavolej port → zformátuj.
 */
final readonly class CliPlaceOrderController
{
    public function __construct(
        private PlaceOrder $placeOrder,
    ) {
    }

    /** @param list<string> $arguments e-mail a částka v korunách */
    public function run(array $arguments): int
    {
        if (count($arguments) !== 2) {
            echo "        Použití: place-order <e-mail> <částka v Kč>\n";

            return 1;
        }

        [$email, $amount] = $arguments;

        $command = new PlaceOrderCommand($email, (int) round((float) $amount * 100));

        try {
            $number = $this->placeOrder->place($command);
        } catch (PaymentDeclined $e) {
            printf("        ✗ Objednávka nebyla založena: %s\n", $e->getMessage());

            return 1;
        }

        printf("        ✓ Objednávka %s založena pro %s\n", $number, $email);

        return 0;
    }
}
