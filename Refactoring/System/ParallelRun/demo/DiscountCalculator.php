<?php

declare(strict_types=1);

interface DiscountCalculator
{
    public function discountInCents(Order $order): int;
}
