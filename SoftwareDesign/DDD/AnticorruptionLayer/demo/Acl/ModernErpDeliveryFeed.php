<?php

declare(strict_types=1);

namespace Acl;

use Domain\Delivery;
use Domain\DeliveryFeed;
use Domain\DeliveryStatus;
use Domain\GoodsReturn;
use Domain\SupplierId;
use ModernErp\ApiClient;

/**
 * Druhá antikorupční vrstva pro nástupnický systém.
 *
 * Jiný formát, jiné pojmy, jiné názvy stavů — a přesto naplňuje TÝŽ
 * port. Doména se kvůli výměně ERP nezmění ani o písmeno.
 *
 * Tohle je odměna za tu vrstvu navíc: až se cizí systém vymění,
 * mění se právě a jen tenhle soubor.
 */
final readonly class ModernErpDeliveryFeed implements DeliveryFeed
{
    public function __construct(
        private ApiClient $api,
    ) {
    }

    public function deliveries(): array
    {
        $deliveries = [];

        foreach ($this->api->fetchInboundShipments() as $shipment) {
            if ($shipment['credit'] === false) {
                $deliveries[] = new Delivery(
                    number: $shipment['reference'],
                    supplierId: SupplierId::fromString($shipment['vendor']['code']),
                    supplierName: $shipment['vendor']['title'],
                    quantity: $shipment['units'],
                    valueInCents: (int) round($shipment['netAmount'] * 100),
                    deliveredOn: new \DateTimeImmutable($shipment['shippedAt']),
                    status: $this->status($shipment['state']),
                );
            }
        }

        return $deliveries;
    }

    public function returns(): array
    {
        $returns = [];

        foreach ($this->api->fetchInboundShipments() as $shipment) {
            if ($shipment['credit'] === true) {
                $returns[] = new GoodsReturn(
                    number: $shipment['reference'],
                    supplierId: SupplierId::fromString($shipment['vendor']['code']),
                    supplierName: $shipment['vendor']['title'],
                    quantity: $shipment['units'],
                    creditedInCents: (int) round($shipment['netAmount'] * 100),
                    returnedOn: new \DateTimeImmutable($shipment['shippedAt']),
                );
            }
        }

        return $returns;
    }

    private function status(string $state): DeliveryStatus
    {
        return match ($state) {
            'ANNOUNCED' => DeliveryStatus::Announced,
            'IN_TRANSIT' => DeliveryStatus::InTransit,
            'RECEIVED' => DeliveryStatus::Received,
            'CANCELLED' => DeliveryStatus::Cancelled,
            default => throw new \InvalidArgumentException(sprintf('Neznámý stav „%s“.', $state)),
        };
    }
}
