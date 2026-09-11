<?php

declare(strict_types=1);

namespace GoodShop\Catalog\Api;

interface Products
{
    public function priceOf(string $sku): int;
}
