<?php

declare(strict_types=1);

namespace Inverted\Domain;

final readonly class OrderLine
{
    public function __construct(
        public int $priceInCents,
        public int $quantity,
    ) {
    }
}
