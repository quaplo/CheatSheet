<?php

declare(strict_types=1);

/** Most ke starému přístupu. Zmizí, až zmizí LegacyDb. */
final class LegacyOrderRepository implements OrderRepository
{
    /** @return list<array{id: int, status: string, total: int}> */
    public function active(): array
    {
        return array_values(array_filter(
            LegacyDb::orders(),
            static fn (array $order): bool => $order['status'] === 'active',
        ));
    }
}
