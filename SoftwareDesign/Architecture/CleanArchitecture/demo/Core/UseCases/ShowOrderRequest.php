<?php

declare(strict_types=1);

namespace Core\UseCases;

/** Vstupní port: jednoduchá data, žádné HTTP, žádná entita. */
final readonly class ShowOrderRequest
{
    public function __construct(public string $orderId)
    {
    }
}
