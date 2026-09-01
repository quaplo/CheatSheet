<?php

declare(strict_types=1);

namespace Leaky;

/**
 * Use-case, do kterého prosáklo byznysové pravidlo.
 *
 * Sám o sobě funguje správně — limit se zkontroluje. Problém je,
 * že je to JEDINÉ místo, kde se kontroluje.
 */
final class PlaceOrderHandler
{
    /** @var list<LooseOrder> */
    public array $saved = [];

    /** @param array<string, int> $limits */
    public function __construct(
        private readonly array $limits,
    ) {
    }

    public function handle(string $customerId, int $totalInCents): LooseOrder
    {
        // ← Byznysové pravidlo v aplikační vrstvě.
        if ($totalInCents > ($this->limits[$customerId] ?? 0)) {
            throw new \DomainException('Objednávka přesahuje úvěrový limit.');
        }

        $order = LooseOrder::place('OBJ-' . count($this->saved), $customerId, $totalInCents);
        $this->saved[] = $order;

        return $order;
    }
}
