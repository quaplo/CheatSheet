<?php

declare(strict_types=1);

namespace Before;

/**
 * PŘED: objednávka, která drží dvě nesouvisející věci.
 *
 * Zvenčí to vypadá jako jedna třída o objednávce. Ve skutečnosti jsou
 * tam dvě skupiny polí a metod, které se navzájem vůbec nedotýkají —
 * a přesně to LCOM4 najde.
 */
final class Order
{
    /** @var list<array{sku: string, price: int, quantity: int}> */
    private array $items = [];

    private string $status = 'nová';

    // …a od téhle chvíle je to o něčem jiném:
    private string $street = '';
    private string $city = '';
    private string $postalCode = '';
    private string $countryCode = 'CZ';

    public function __construct(public readonly string $number)
    {
    }

    // --- objednávka -------------------------------------------------------

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

    // --- doručovací adresa ------------------------------------------------

    public function setAddress(string $street, string $city, string $postalCode, string $countryCode): void
    {
        $this->street = $street;
        $this->city = $city;
        $this->postalCode = $postalCode;
        $this->countryCode = $countryCode;
    }

    public function formattedAddress(): string
    {
        return sprintf('%s, %s %s, %s', $this->street, $this->postalCode, $this->city, $this->countryCode);
    }

    public function isDomestic(): bool
    {
        return $this->countryCode === 'CZ';
    }

    public function postalCodeDigits(): string
    {
        return preg_replace('/\D/', '', $this->postalCode);
    }
}
