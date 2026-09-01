<?php

declare(strict_types=1);

namespace Contexts;

/**
 * Doprava. Poslední krok — a schválně ten, který se kompenzuje nejhůř.
 *
 * Jakmile zásilka fyzicky odjede, žádná kompenzace ji nevrátí; jde
 * jen dopředu (reklamace, vratka). Proto se nevratné kroky dávají
 * NAKONEC — viz „pivotní krok“ v README.
 */
final class ShippingContext
{
    public bool $failNext = false;

    /** @var array<string, string> */
    public array $shipments = [];

    /** @var list<string> */
    public array $log = [];

    public function schedule(string $orderId): string
    {
        if ($this->failNext) {
            $this->failNext = false;

            throw new \RuntimeException('Doprava: dopravce nepřijal zásilku.');
        }

        $shipmentId = 'SHP-' . strtoupper(substr(md5($orderId), 0, 6));
        $this->shipments[$shipmentId] = $orderId;
        $this->log[] = sprintf('naplánováno %s', $shipmentId);

        return $shipmentId;
    }

    public function cancel(string $shipmentId): void
    {
        if (isset($this->shipments[$shipmentId]) === false) {
            return;
        }

        unset($this->shipments[$shipmentId]);
        $this->log[] = sprintf('zrušeno %s', $shipmentId);
    }
}
