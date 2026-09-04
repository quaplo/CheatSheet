<?php

declare(strict_types=1);

namespace Recommendation;

/**
 * Historie prohlížení — vstup pro jádro, ale sama jádrem není.
 * Je to obyčejný sběr dat, který by vypadal stejně v jakémkoli e-shopu.
 */
final class ViewHistory
{
    /** @var list<string> */
    private array $skus = [];

    public function record(string $sku): void
    {
        $this->skus[] = $sku;
    }

    /** @return list<string> */
    public function all(): array
    {
        return $this->skus;
    }
}
