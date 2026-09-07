<?php

declare(strict_types=1);

/** Statická konfigurace, na kterou sahá půlka aplikace. */
final class Config
{
    public static function vatPercent(): int
    {
        return 21;
    }
}
