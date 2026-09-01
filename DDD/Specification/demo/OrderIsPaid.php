<?php

declare(strict_types=1);

/** Objednávka je zaplacená. */
final class OrderIsPaid extends OrderSpecification
{
    public function isSatisfiedBy(Order $order): bool
    {
        return $order->isPaid;
    }

    public function describe(): string
    {
        return 'objednávka je zaplacená';
    }
}
