<?php

declare(strict_types=1);

/**
 * Události jsou fakta o tom, co se stalo.
 *
 * Minulý čas ve jméně není kosmetika — událost se nedá odmítnout
 * ani zrušit, protože se už stala. To ji odlišuje od příkazu.
 */
interface Event
{
    public function happenedAt(): string;
}

final readonly class OrderPlaced implements Event
{
    public function __construct(
        public string $orderId,
        public string $shippingAddress,
        private string $at,
    ) {
    }

    public function happenedAt(): string
    {
        return $this->at;
    }
}

final readonly class ItemAdded implements Event
{
    public function __construct(
        public string $sku,
        public int $priceInCents,
        public int $quantity,
        private string $at,
    ) {
    }

    public function happenedAt(): string
    {
        return $this->at;
    }
}

final readonly class AddressChanged implements Event
{
    public function __construct(
        public string $shippingAddress,
        private string $at,
    ) {
    }

    public function happenedAt(): string
    {
        return $this->at;
    }
}

final readonly class OrderShipped implements Event
{
    public function __construct(
        public string $carrier,
        private string $at,
    ) {
    }

    public function happenedAt(): string
    {
        return $this->at;
    }
}
