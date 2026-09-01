<?php

declare(strict_types=1);

namespace ModernErp;

/** Nástupce. Rozumný formát, jiné pojmy — ale pořád cizí. */
final class ApiClient
{
    /** @return list<array<string, mixed>> */
    public function fetchInboundShipments(): array
    {
        return [
            ['reference' => 'SHP-9001', 'vendor' => ['code' => 'V-4711', 'title' => 'Mlýny Brno a.s.'],
             'units' => 120, 'netAmount' => 48500.00, 'currency' => 'CZK',
             'shippedAt' => '2026-09-01T08:00:00+02:00', 'state' => 'IN_TRANSIT', 'credit' => false],

            ['reference' => 'SHP-9002', 'vendor' => ['code' => 'V-4711', 'title' => 'Mlýny Brno a.s.'],
             'units' => 15, 'netAmount' => 6062.50, 'currency' => 'CZK',
             'shippedAt' => '2026-09-03T10:30:00+02:00', 'state' => 'RECEIVED', 'credit' => true],
        ];
    }
}
