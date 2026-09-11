<?php

declare(strict_types=1);

namespace BadShop\Catalog\Api;

interface Products
{
    public function priceOf(string $sku): int;
}
