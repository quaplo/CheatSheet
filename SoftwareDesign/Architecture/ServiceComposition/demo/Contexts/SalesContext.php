<?php

declare(strict_types=1);

namespace Contexts;

/**
 * Cizí kontext. Ven vystavuje JEN tohle — svoje use-case.
 *
 * Kompozice smí volat tyhle metody a nic jiného. Ne repository,
 * ne doménové objekty, ne databázi. To je celá dohoda.
 */
final class SalesContext
{
    public bool $isDown = false;
    public int $calls = 0;

    /** @return array{orderId: string, customer: string, totalInCents: int, placedAt: string} */
    public function orderSummary(string $orderId): array
    {
        $this->calls++;
        $this->assertUp('Sales');

        return [
            'orderId' => $orderId,
            'customer' => 'Pekárna Novák s.r.o.',
            'totalInCents' => 620000,
            'placedAt' => '2026-09-01',
        ];
    }

    public function placeOrder(string $customerId, int $totalInCents): string
    {
        $this->calls++;
        $this->assertUp('Sales');

        return 'OBJ-4711';
    }

    private function assertUp(string $name): void
    {
        if ($this->isDown) {
            throw new \RuntimeException(sprintf('Kontext %s není dostupný.', $name));
        }
    }
}
