<?php

declare(strict_types=1);

namespace Invoicing;

/** Obecná podoblast: fakturace vypadá všude stejně. */
final class Invoice
{
    public function __construct(public readonly string $number)
    {
    }
}
