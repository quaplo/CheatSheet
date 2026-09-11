<?php

declare(strict_types=1);

namespace BadShop\Ordering\Domain;

/** Vnitřek modulu. Nikdo zvenčí o něm nemá vědět. */
final class Order
{
    /** @var list<OrderLine> */
    private array $lines = [];

    public function __construct(public readonly string $id)
    {
    }

    public function addLine(string $sku, int $priceInCents): void
    {
        $this->lines[] = new OrderLine($sku, $priceInCents);
    }

    public function totalInCents(): int
    {
        return array_sum(array_map(static fn (OrderLine $line): int => $line->priceInCents, $this->lines));
    }
}
