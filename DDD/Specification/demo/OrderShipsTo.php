<?php

declare(strict_types=1);

/** Objednávka jde do dané země. */
final class OrderShipsTo extends OrderSpecification
{
    public function __construct(
        private readonly string $countryCode,
    ) {
    }

    public function isSatisfiedBy(Order $order): bool
    {
        return $order->countryCode === $this->countryCode;
    }

    public function describe(): string
    {
        return sprintf('doručuje se do %s', $this->countryCode);
    }
}
