<?php

declare(strict_types=1);

namespace After;

/** Storno — zrušení objednávky zákazníkem před expedicí. */
final readonly class Cancellation
{
    public function __construct(public string $reason)
    {
    }
}
