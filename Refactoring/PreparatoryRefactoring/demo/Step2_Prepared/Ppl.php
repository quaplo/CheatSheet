<?php

declare(strict_types=1);

namespace Step2_Prepared;

final class Ppl implements ShippingMethod
{
    use FreeOverThreshold;

    public function code(): string
    {
        return 'ppl';
    }

    public function priceInCents(int $weightInGrams, int $orderValueInCents): int
    {
        return $this->isFree($orderValueInCents) ? 0 : 9900;
    }
}
