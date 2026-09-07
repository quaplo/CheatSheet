<?php

declare(strict_types=1);

final class OrderReport
{
    public function __construct(private readonly OrderService $service)
    {
    }

    public function summary(): string
    {
        return $this->service->activeCount() . ' objednávek za ' . $this->service->activeTotal() . ' haléřů';
    }
}
