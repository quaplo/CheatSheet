<?php

declare(strict_types=1);

/**
 * Objednávka má hodnotu alespoň X.
 *
 * Specifikace může mít parametry — díky tomu je z jedné třídy použitelných
 * libovolně mnoho konkrétních pravidel.
 */
final class OrderTotalAtLeast extends OrderSpecification
{
    public function __construct(
        private readonly int $thresholdInCents,
    ) {
    }

    public function isSatisfiedBy(Order $order): bool
    {
        return $order->totalInCents >= $this->thresholdInCents;
    }

    public function describe(): string
    {
        return sprintf('hodnota je alespoň %s Kč', number_format($this->thresholdInCents / 100, 0, ',', ' '));
    }
}
