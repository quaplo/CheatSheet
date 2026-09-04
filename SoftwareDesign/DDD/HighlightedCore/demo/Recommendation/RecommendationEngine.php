<?php

declare(strict_types=1);

namespace Recommendation;

use CoreDomain;

#[CoreDomain('Důvod, proč k nám zákazník chodí — najde zboží, které by sám nenašel.')]
final class RecommendationEngine
{
    public function recommendFor(string $customerId): array
    {
        return ['MON-27', 'KLA-01'];
    }
}
