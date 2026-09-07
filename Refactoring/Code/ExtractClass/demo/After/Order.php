<?php

declare(strict_types=1);

namespace After;

/**
 * PO: objednávka je o objednávce.
 *
 * Adresu drží, ale nezná její vnitřek — na formátování ani na to,
 * jestli je tuzemská, se ptá jí.
 */
final class Order
{
    /** @var list<array{sku: string, price: int, quantity: int}> */
    private array $items = [];

    private string $status = 'nová';

    private ?DeliveryAddress $address = null;

    public function __construct(public readonly string $number)
    {
    }

    public function addItem(string $sku, int $price, int $quantity): void
    {
        $this->items[] = ['sku' => $sku, 'price' => $price, 'quantity' => $quantity];
    }

    public function totalInCents(): int
    {
        $total = 0;

        foreach ($this->items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }

    public function itemCount(): int
    {
        return count($this->items);
    }

    public function confirm(): void
    {
        $this->status = 'potvrzená';
    }

    public function status(): string
    {
        return $this->status;
    }

    public function deliverTo(DeliveryAddress $address): void
    {
        $this->address = $address;
    }

    public function address(): ?DeliveryAddress
    {
        return $this->address;
    }
}
