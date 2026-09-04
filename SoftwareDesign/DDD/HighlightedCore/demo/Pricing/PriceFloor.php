<?php

declare(strict_types=1);

namespace Pricing;

use CoreDomain;

#[CoreDomain('Spodní hranice, pod kterou cena nesmí klesnout — chrání důvěru i marži.')]
final readonly class PriceFloor
{
    public function __construct(public int $minimumInCents)
    {
    }
}
