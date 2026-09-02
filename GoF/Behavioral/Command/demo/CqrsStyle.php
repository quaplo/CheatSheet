<?php

declare(strict_types=1);

/**
 * Pro srovnání: „command“ ve světě CQRS a command busů.
 *
 * Vypadá to stejně, ale je to jiná věc. Tohle je čistá DATOVÁ
 * struktura — nemá execute(), nezná příjemce a sama nic neumí.
 * Práci dělá handler, kterého k ní přiřadí bus.
 */
final readonly class PlaceOrder
{
    public function __construct(
        public string $customerId,
        public int $totalInCents,
    ) {
    }
}

final class PlaceOrderHandler
{
    /** @var list<string> */
    public array $handled = [];

    public function __invoke(PlaceOrder $command): void
    {
        $this->handled[] = sprintf(
            'objednávka pro %s za %d Kč',
            $command->customerId,
            intdiv($command->totalInCents, 100),
        );
    }
}
