<?php

declare(strict_types=1);

namespace Layered\Infrastructure;

final class Db
{
    private static bool $connected = false;

    public static function connect(): void
    {
        self::$connected = true;
    }

    /** @return list<array{price: int, quantity: int}> */
    public static function lines(string $orderId): array
    {
        if (!self::$connected) {
            throw new \RuntimeException('Není připojení k databázi.');
        }

        return [['price' => 12900, 'quantity' => 2], ['price' => 4900, 'quantity' => 1]];
    }
}
