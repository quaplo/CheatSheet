<?php

declare(strict_types=1);

/**
 * Měna. Enum je v PHP nejjednodušší forma value objectu — instance je
 * z podstaty neměnná, porovnává se hodnotou a neplatná prostě neexistuje.
 */
enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';

    public function symbol(): string
    {
        return match ($this) {
            self::CZK => 'Kč',
            self::EUR => '€',
        };
    }
}
