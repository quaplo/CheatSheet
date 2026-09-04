<?php

declare(strict_types=1);

namespace Recommendation;

use CoreDomain;

#[CoreDomain('Míra podobnosti chování zákazníků; jádro doporučovacího modelu.')]
final readonly class SimilarityScore
{
    public function __construct(public float $value)
    {
    }
}
