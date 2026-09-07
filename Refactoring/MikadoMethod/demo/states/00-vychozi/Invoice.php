<?php

declare(strict_types=1);

final class Invoice
{
    public function line(int $amountInCents): string
    {
        return 'Celkem ' . (new PriceCalculator())->withVat($amountInCents) . ' haléřů';
    }
}
