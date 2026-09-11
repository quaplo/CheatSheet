<?php

declare(strict_types=1);

namespace BadShop\Catalog\Domain;

final readonly class Product
{
    public function __construct(
        public string $sku,
        public int $priceInCents,
    ) {
    }
}
