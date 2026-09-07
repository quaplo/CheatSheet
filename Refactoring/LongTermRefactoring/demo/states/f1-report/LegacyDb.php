<?php

declare(strict_types=1);

/** Starý přístup k datům. Statický, netestovatelný, všude. */
final class LegacyDb
{
    /** @return list<array{id: int, status: string, total: int}> */
    public static function orders(): array
    {
        return [
            ['id' => 1, 'status' => 'active', 'total' => 12000],
            ['id' => 2, 'status' => 'cancelled', 'total' => 5000],
            ['id' => 3, 'status' => 'active', 'total' => 8000],
        ];
    }
}
