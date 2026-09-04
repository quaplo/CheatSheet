<?php

declare(strict_types=1);

namespace Ordering;

/** Podpora: objednávkový proces má každý e-shop podobný. */
final class Order
{
    public function __construct(public readonly string $number)
    {
    }
}
