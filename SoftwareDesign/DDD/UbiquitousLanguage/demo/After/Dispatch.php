<?php

declare(strict_types=1);

namespace After;

/** Expedice — předání zásilky dopravci. */
final readonly class Dispatch
{
    public function __construct(public string $carrier)
    {
    }
}
