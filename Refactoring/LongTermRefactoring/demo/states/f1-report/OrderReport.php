<?php

declare(strict_types=1);

final class OrderReport
{
    public function summary(): string
    {
        $service = new OrderService();

        return $service->activeCount() . ' objednávek za ' . $service->activeTotal() . ' haléřů';
    }
}
