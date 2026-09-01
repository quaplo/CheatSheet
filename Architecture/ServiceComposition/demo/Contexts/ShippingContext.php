<?php

declare(strict_types=1);

namespace Contexts;

final class ShippingContext
{
    public bool $isDown = false;
    public int $calls = 0;

    /** @var list<string> */
    public array $scheduled = [];

    /** @return array{trackingNumber: string, carrier: string, estimatedAt: string} */
    public function trackingFor(string $orderId): array
    {
        $this->calls++;
        $this->assertUp();

        return ['trackingNumber' => 'CZ123456789', 'carrier' => 'PPL', 'estimatedAt' => '2026-09-05'];
    }

    public function scheduleDelivery(string $orderId): string
    {
        $this->calls++;
        $this->assertUp();

        $this->scheduled[] = $orderId;

        return 'CZ' . random_int(100000000, 999999999);
    }

    private function assertUp(): void
    {
        if ($this->isDown) {
            throw new \RuntimeException('Kontext Shipping není dostupný.');
        }
    }
}
