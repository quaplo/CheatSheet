<?php

declare(strict_types=1);

/** Cílový stav, na kterém se tým dohodl: přístup k datům přes rozhraní. */
interface OrderRepository
{
    /** @return list<array{id: int, status: string, total: int}> */
    public function active(): array;
}
