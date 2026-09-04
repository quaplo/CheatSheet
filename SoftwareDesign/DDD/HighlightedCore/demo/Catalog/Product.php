<?php

declare(strict_types=1);

namespace Catalog;

/** Podpora: katalog má každý e-shop. */
final class Product
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
    ) {
    }
}
