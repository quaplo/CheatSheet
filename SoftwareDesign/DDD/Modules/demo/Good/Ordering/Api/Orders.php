<?php

declare(strict_types=1);

namespace GoodShop\Ordering\Api;

/** Vstupní bod modulu. Uvnitř si dělá, co chce. */
interface Orders
{
    public function summaryOf(string $orderId): OrderSummary;
}
