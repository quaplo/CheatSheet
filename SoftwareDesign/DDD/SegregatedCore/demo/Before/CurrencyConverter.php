<?php

declare(strict_types=1);

namespace Before;

final class CurrencyConverter
{
    public function toEur(int $cents): float
    {
        return round($cents / 100 / 25.2, 2);
    }
}
