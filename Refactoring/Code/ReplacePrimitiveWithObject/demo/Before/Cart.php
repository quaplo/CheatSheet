<?php

declare(strict_types=1);

namespace Before;

/**
 * Košík, který pracuje s SKU jako s řetězcem.
 *
 * Porovnávání je prosté ===, takže „MON-27" a „mon-27" jsou pro něj
 * dvě různé položky. Nikdo to nezamýšlel; prostě to tak vyšlo.
 */
final class Cart
{
    /** @var array<string, int> sku => množství */
    private array $lines = [];

    public function add(string $sku, int $quantity): void
    {
        if (isset($this->lines[$sku])) {
            $this->lines[$sku] += $quantity;

            return;
        }

        $this->lines[$sku] = $quantity;
    }

    /** @return array<string, int> */
    public function lines(): array
    {
        return $this->lines;
    }

    public function distinctItems(): int
    {
        return count($this->lines);
    }
}
