<?php

declare(strict_types=1);

namespace Contexts;

/**
 * Cizí kontext. Má vlastní databázi a vlastní transakce — do našich
 * nevidí a my do jeho ne.
 *
 * Všimni si, že rezervace i její zrušení jsou obě NORMÁLNÍ operace
 * toho kontextu. Žádný „rollback“ neexistuje; sklad zná jen rezervovat
 * a uvolnit.
 */
final class StockContext
{
    public bool $failNext = false;

    /** @var array<string, int> */
    public array $reserved = [];

    /** @var list<string> */
    public array $log = [];

    public function reserve(string $orderId, string $sku, int $quantity): string
    {
        if ($this->failNext) {
            $this->failNext = false;

            throw new \RuntimeException('Sklad: zboží není dostupné.');
        }

        $reservationId = 'RES-' . strtoupper(substr(md5($orderId), 0, 6));
        $this->reserved[$reservationId] = $quantity;
        $this->log[] = sprintf('rezervováno %d× %s (%s)', $quantity, $sku, $reservationId);

        return $reservationId;
    }

    /** Kompenzace — a je to obyčejná operace skladu, ne magie. */
    public function release(string $reservationId): void
    {
        if (isset($this->reserved[$reservationId]) === false) {
            $this->log[] = sprintf('uvolnění %s přeskočeno (už není)', $reservationId);

            return;   // idempotence: druhé volání nic nezkazí
        }

        unset($this->reserved[$reservationId]);
        $this->log[] = sprintf('uvolněno %s', $reservationId);
    }
}
