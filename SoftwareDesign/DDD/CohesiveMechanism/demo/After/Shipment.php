<?php

declare(strict_types=1);

namespace After;

use BoxSize;
use PackableItem;
use Packing\Packer;
use Packing\PackingPlan;

/**
 * PO: doména vyjadřuje „co", mechanismus řeší „jak".
 *
 * Třída se zkrátila na to, o čem je: co je zásilka, kdy se smí
 * odeslat a co obsahuje. Balení si vyžádá u mechanismu.
 *
 * Všimni si, že závislost je na ROZHRANÍ Packer, ne na konkrétní
 * heuristice — doména neví ani to, jaký algoritmus běží.
 */
final class Shipment
{
    /** @var list<PackableItem> */
    private array $items = [];

    /** @var list<BoxSize> */
    private array $availableBoxes = [];

    private bool $dispatched = false;

    public function __construct(public readonly string $number)
    {
    }

    public function addItem(PackableItem $item): void
    {
        if ($this->dispatched) {
            throw new \DomainException('Odeslanou zásilku už nelze měnit.');
        }

        $this->items[] = $item;
    }

    public function offerBox(BoxSize $box): void
    {
        $this->availableBoxes[] = $box;
    }

    public function canBeDispatched(): bool
    {
        return !$this->dispatched && $this->items !== [];
    }

    public function dispatch(): void
    {
        if (!$this->canBeDispatched()) {
            throw new \DomainException('Zásilku nelze odeslat.');
        }

        $this->dispatched = true;
    }

    /** Doména řekne, co chce. Jak se to spočítá, ji nezajímá. */
    public function packUsing(Packer $packer): PackingPlan
    {
        return $packer->pack($this->items, $this->availableBoxes);
    }
}
