<?php

declare(strict_types=1);

/**
 * Táž objednávka jako řádek v tabulce.
 *
 * Každý zápis přepíše to, co tam bylo. Stav je pravda — a všechno
 * ostatní je pryč.
 */
final class SnapshotOrder
{
    public string $shippingAddress = '';

    public int $itemsTotalInCents = 0;

    public ?string $carrier = null;

    public function addItem(int $priceInCents, int $quantity): void
    {
        $this->itemsTotalInCents += $priceInCents * $quantity;
    }

    public function changeAddress(string $address): void
    {
        $this->shippingAddress = $address;
    }

    public function ship(string $carrier): void
    {
        $this->carrier = $carrier;
    }
}
