<?php

declare(strict_types=1);

namespace Application;

/** Port pro údaj z jiného agregátu — použije ho use-case, ne doména. */
interface CustomerCredit
{
    public function limitFor(string $customerId): int;
}
