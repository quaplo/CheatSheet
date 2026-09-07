<?php

declare(strict_types=1);

namespace After;

/**
 * Košík, který pracuje s typem Sku.
 *
 * Porovnávání je na typu, ne na řetězci — takže „mon-27" a „MON-27"
 * jsou tatáž položka, protože obojí projde toutéž normalizací.
 */
final class Cart
{
    /** @var array<string, array{sku: Sku, quantity: int}> */
    private array $lines = [];

    public function add(Sku $sku, int $quantity): void
    {
        $key = $sku->value;

        if (isset($this->lines[$key])) {
            $this->lines[$key]['quantity'] += $quantity;

            return;
        }

        $this->lines[$key] = ['sku' => $sku, 'quantity' => $quantity];
    }

    /** @return array<string, array{sku: Sku, quantity: int}> */
    public function lines(): array
    {
        return $this->lines;
    }

    public function distinctItems(): int
    {
        return count($this->lines);
    }
}
