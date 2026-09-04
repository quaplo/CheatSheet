<?php

declare(strict_types=1);

namespace After;

/** Dobropis — doklad o vrácení peněz zákazníkovi. */
final class CreditNote
{
    public function __construct(public readonly string $number)
    {
    }
}
